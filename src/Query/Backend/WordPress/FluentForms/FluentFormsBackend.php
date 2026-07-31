<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\FluentForms;

use DataKit\DataViews\Query\Backend\WordPress\AbstractWpdbBackend;
use DataKit\DataViews\Query\Backend\WordPress\SqlFilterCompiler;
use DataKit\DataViews\Query\Backend\WordPress\WpdbCompiledQuery;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\Source;

/**
 * Fluent Forms query backend.
 *
 * Queries `fluentform_submissions` for system columns, LEFT JOINing
 * `fluentform_entry_details` once per referenced form field.
 *
 * Fluent Forms differs from the WPForms/Gravity Forms shape in three ways
 * that this backend is built around:
 *
 *   1. Answers are keyed by a STRING `field_name` (the input's HTML name),
 *      not a numeric field id.
 *   2. A multi-input field (name, address) stores one row per sub-input,
 *      identified by the (`field_name`, `sub_field_name`) column PAIR. The
 *      field key encodes the pair as `field:names.first_name`.
 *   3. Payment, geography and device data are native columns on the
 *      submission row, so they cost no JOIN.
 *
 * @since $ver$
 */
final class FluentFormsBackend extends AbstractWpdbBackend
{
    public const SOURCE_TYPE = 'fluent_forms';

    /**
     * Prefix for per-form field keys.
     *
     * Fluent Forms field names are strings, so PHP's array-key int cast
     * (the trap that forced this convention for WPForms) does not apply.
     * The prefix stays anyway: a raw field name like `status` or `id`
     * would collide with a system column.
     */
    public const FIELD_PREFIX = 'field:';

    /**
     * Field keys mapped to their `fluentform_submissions` column.
     *
     * `created_at` / `updated_at` are Fluent Forms' real column names, so
     * the cross-source semantic keys other backends alias resolve here
     * without aliases.
     *
     * @var array<string, string>
     */
    private const SUBMISSION_COLUMNS = [
        'id' => 'id',
        'form_id' => 'form_id',
        'serial_number' => 'serial_number',
        'source_url' => 'source_url',
        'user_id' => 'user_id',
        'status' => 'status',
        'is_favourite' => 'is_favourite',
        'browser' => 'browser',
        'device' => 'device',
        'ip' => 'ip',
        'city' => 'city',
        'country' => 'country',
        'payment_status' => 'payment_status',
        'payment_method' => 'payment_method',
        'payment_type' => 'payment_type',
        'currency' => 'currency',
        'payment_total' => 'payment_total',
        'total_paid' => 'total_paid',
        'created_at' => 'created_at',
        'updated_at' => 'updated_at',
    ];

    /**
     * Maximum EAV JOINs per query.
     *
     * Each referenced form field costs one LEFT JOIN against
     * `fluentform_entry_details`, and MySQL's planner degrades sharply past
     * a few dozen. Same ceiling as the WPForms backend: the tables have the
     * same one-row-per-answer shape, so the same plan-degradation reasoning
     * applies. Raise it only with a measured plan on a wide form.
     */
    private const MAX_FIELD_JOINS = 40;

    /** @var string[] Statuses excluded when a scope names none. */
    private const EXCLUDED_STATUSES = ['trashed', 'spam'];

    /** @var string[] */
    private const DATETIME_KEYS = ['created_at', 'updated_at'];

    /** @var string[] */
    private const NUMERIC_KEYS = [
        'id', 'form_id', 'serial_number', 'user_id', 'is_favourite',
        'payment_total', 'total_paid',
    ];

    /**
     * Elements whose stored answer is numeric.
     *
     * @var string[]
     */
    private const NUMERIC_ELEMENTS = [
        'input_number', 'ratings', 'net_promoter', 'rangeslider',
        'custom_payment_component', 'item_quantity_component',
        'payment_summary_component', 'subscription_payment_component',
    ];

    /**
     * Elements that store one EAV row per selected value
     * (`sub_field_name` = 0, 1, ...), so their JOIN must not pin
     * `sub_field_name` and a grouped query counts one row per selection —
     * unnest semantics, the analytics-correct shape for "count by option".
     *
     * @var string[]
     */
    private const MULTI_VALUE_ELEMENTS = [
        'input_checkbox', 'multi_select', 'input_repeat', 'repeater_field',
        'tabular_grid', 'taxonomy',
    ];

    /** @var string[] Accumulated JOIN clauses for the query being compiled. */
    private array $joins = [];

    /** @var array<string, ColumnType> Column types by field key, captured during compile. */
    private array $fieldTypes = [];

    /**
     * Field keys that resolved to a multi-value element during describe().
     *
     * @var array<string, bool>
     */
    private array $multiValueKeys = [];

    private FluentFormsFormRepository $forms;

    public function __construct(?FluentFormsFormRepository $forms = null)
    {
        parent::__construct();

        $this->forms = $forms ?? new FluentFormsApiFormRepository();
    }

    /**
     * Whether Fluent Forms submissions can be queried here.
     *
     * The free plugin creates and populates `fluentform_submissions`, but
     * the table is still checked rather than just the plugin: a site can
     * deactivate Fluent Forms while keeping (queryable) data, and the
     * backend must not register against a missing table, where every query
     * would fail rather than the engine answering "no backend registered".
     */
    public static function isAvailable(): bool
    {
        global $wpdb;

        if (!isset($wpdb) || !defined('FLUENTFORM')) {
            return false;
        }

        static $exists = null;

        if ($exists === null) {
            $table = $wpdb->prefix . 'fluentform_submissions';

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== null;
        }

        return $exists;
    }

    public function sourceType(): string
    {
        return self::SOURCE_TYPE;
    }

    /**
     * Create a source for Fluent Forms submissions.
     *
     * @param int[]    $formIds Form IDs to include; empty means all forms.
     * @param string[] $status  Submission statuses; empty excludes trashed and spam.
     */
    public static function source(array $formIds = [], array $status = []): Source
    {
        return new Source(self::SOURCE_TYPE, 'submissions', [
            'form_ids' => $formIds,
            'status' => $status,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @return Capability[]
     */
    public function capabilities(): array
    {
        return [
            Capability::FilterEq, Capability::FilterNeq,
            Capability::FilterGt, Capability::FilterGte,
            Capability::FilterLt, Capability::FilterLte,
            Capability::FilterIn, Capability::FilterNotIn,
            Capability::FilterBetween,
            Capability::FilterContains, Capability::FilterNotContains,
            Capability::FilterStartsWith,
            Capability::FilterIsEmpty, Capability::FilterIsNotEmpty,
            Capability::AggCount, Capability::AggSum, Capability::AggAvg,
            Capability::AggMin, Capability::AggMax, Capability::AggCountDistinct,
            Capability::GroupBy, Capability::Having, Capability::OrConditions,
            Capability::TimeBucket, Capability::Search, Capability::OrderBy,
            Capability::LimitOffset,
        ];
    }

    public function describe(array $scope): BackendSchema
    {
        $fields = $this->systemFieldSchemas();

        foreach ($this->scopeFormIds($scope) as $formId) {
            foreach ($this->discoverFormFields($formId) as $field) {
                $fields[] = $field;
            }
        }

        return new BackendSchema(
            self::SOURCE_TYPE,
            'Fluent Forms Submissions',
            'Fluent Forms submissions with per-form answers, payment and device metadata.',
            $this->capabilities(),
            $fields,
        );
    }

    /**
     * Columns present on `fluentform_submissions` for every form.
     *
     * @return FieldSchema[]
     */
    private function systemFieldSchemas(): array
    {
        $allOps = ComparisonOperator::cases();

        return [
            new FieldSchema('id', 'Submission ID', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('form_id', 'Form ID', ColumnType::Integer, $allOps),
            new FieldSchema('serial_number', 'Serial Number', ColumnType::Integer, $allOps),
            new FieldSchema('source_url', 'Source URL', ColumnType::String, $allOps, sortable: false),
            new FieldSchema('user_id', 'User ID', ColumnType::Integer, $allOps),
            new FieldSchema(
                'status',
                'Status',
                ColumnType::String,
                $allOps,
                enumValues: [
                    'unread' => 'Unread',
                    'read' => 'Read',
                    'spam' => 'Spam',
                    'trashed' => 'Trash',
                ],
            ),
            new FieldSchema('is_favourite', 'Favorite', ColumnType::Integer, $allOps, enumValues: ['0' => 'No', '1' => 'Yes']),
            new FieldSchema('browser', 'Browser', ColumnType::String, $allOps),
            new FieldSchema('device', 'Device', ColumnType::String, $allOps),
            new FieldSchema('ip', 'IP Address', ColumnType::String, $allOps),
            new FieldSchema('city', 'City', ColumnType::String, $allOps),
            new FieldSchema('country', 'Country', ColumnType::String, $allOps),
            new FieldSchema('payment_status', 'Payment Status', ColumnType::String, $allOps),
            new FieldSchema('payment_method', 'Payment Method', ColumnType::String, $allOps),
            new FieldSchema('payment_type', 'Payment Type', ColumnType::String, $allOps),
            new FieldSchema('currency', 'Currency', ColumnType::String, $allOps),
            new FieldSchema(
                'payment_total',
                'Payment Total',
                ColumnType::Float,
                $allOps,
                aggregatable: true,
                description: 'Stored by Fluent Forms in the currency subunit (cents).',
            ),
            new FieldSchema(
                'total_paid',
                'Total Paid',
                ColumnType::Float,
                $allOps,
                aggregatable: true,
                description: 'Stored by Fluent Forms in the currency subunit (cents).',
            ),
            new FieldSchema(
                'created_at',
                'Date Created',
                ColumnType::Datetime,
                $allOps,
                aggregatable: true,
                timezone: 'local',
            ),
            new FieldSchema(
                'updated_at',
                'Date Modified',
                ColumnType::Datetime,
                $allOps,
                aggregatable: true,
                timezone: 'local',
            ),
        ];
    }

    /**
     * Discover the answerable fields on one form.
     *
     * A container field (name, address) is advertised through its
     * sub-inputs only: Fluent Forms writes one `fluentform_entry_details`
     * row per sub-input and none for the container itself, so a key for the
     * bare container would advertise a column that can never match a row.
     *
     * @return FieldSchema[]
     */
    private function discoverFormFields(int $formId): array
    {
        $allOps = ComparisonOperator::cases();
        $fields = [];

        foreach ($this->forms->getFields($formId) as $definition) {
            $name = $definition['name'];
            $element = $definition['element'];

            if ($this->sanitizeSegment($name) === null) {
                continue;
            }

            $label = $definition['label'] !== '' ? $definition['label'] : $name;
            $children = $definition['children'];

            if ($children !== []) {
                foreach ($children as $subName => $subLabel) {
                    if ($this->sanitizeSegment($subName) === null) {
                        continue;
                    }

                    $fields[] = new FieldSchema(
                        self::FIELD_PREFIX . $name . '.' . $subName,
                        $label . ': ' . ($subLabel !== '' ? $subLabel : $subName),
                        ColumnType::String,
                        $allOps,
                        description: "Form field: {$element}",
                    );
                }

                continue;
            }

            $columnType = in_array($element, self::NUMERIC_ELEMENTS, true)
                ? ColumnType::Float
                : ColumnType::String;

            $key = self::FIELD_PREFIX . $name;

            if (in_array($element, self::MULTI_VALUE_ELEMENTS, true)) {
                $this->multiValueKeys[$key] = true;
            }

            $fields[] = new FieldSchema(
                $key,
                $label,
                $columnType,
                $allOps,
                aggregatable: $columnType === ColumnType::Float,
                description: "Form field: {$element}",
            );
        }

        return $fields;
    }

    protected function buildColumnMap(Query $query, BackendSchema $schema): array
    {
        $this->joins = [];
        $this->fieldTypes = [];

        foreach ($schema->fields as $field) {
            $this->fieldTypes[$field->key] = $field->type;
        }

        // describe() records which keys are multi-value while it walks the
        // form; on a cold compile (schema built elsewhere) re-walk the scope
        // so the JOIN shape cannot depend on call order.
        if ($this->multiValueKeys === []) {
            foreach ($this->scopeFormIds($query->source->scope) as $formId) {
                $this->discoverFormFields($formId);
            }
        }

        $columnMap = [];

        foreach ($this->collectFieldKeys($query, $schema) as $key) {
            $key = (string) $key;

            if (isset(self::SUBMISSION_COLUMNS[$key])) {
                $columnMap[$key] = 's.' . self::SUBMISSION_COLUMNS[$key];
                continue;
            }

            $pair = $this->fieldPairFor($key);

            if ($pair === null || $this->joinCounter >= self::MAX_FIELD_JOINS) {
                continue;
            }

            $alias = $this->nextJoinAlias('d');
            $this->joins[] = $this->answerJoin($alias, $pair['field'], $pair['sub']);
            $columnMap[$key] = $alias . '.field_value';
        }

        return $columnMap;
    }

    /**
     * Resolve the (`field_name`, `sub_field_name`) pair a field key selects.
     *
     * `sub` is null when the JOIN must not constrain `sub_field_name`
     * (multi-value elements), and the empty string for a plain single-value
     * field, which Fluent Forms stores with `sub_field_name = ''`.
     *
     * Returns null for anything that could not be a Fluent Forms field
     * name, so a bogus key never reaches the SQL string.
     *
     * @return array{field: string, sub: ?string}|null
     */
    private function fieldPairFor(string $key): ?array
    {
        if (!str_starts_with($key, self::FIELD_PREFIX)) {
            return null;
        }

        $name = substr($key, strlen(self::FIELD_PREFIX));
        $sub = null;

        $dot = strpos($name, '.');

        if ($dot !== false) {
            $sub = substr($name, $dot + 1);
            $name = substr($name, 0, $dot);
        }

        $name = $this->sanitizeSegment($name);

        if ($name === null) {
            return null;
        }

        if ($sub !== null) {
            $sub = $this->sanitizeSegment($sub);

            if ($sub === null) {
                return null;
            }

            return ['field' => $name, 'sub' => $sub];
        }

        return [
            'field' => $name,
            'sub' => isset($this->multiValueKeys[$key]) ? null : '',
        ];
    }

    /**
     * Restrict a field-name segment to characters that can appear in a
     * Fluent Forms input name. The value is inlined into a JOIN literal
     * (see {@see answerJoin()}), and this is what keeps that safe.
     */
    private function sanitizeSegment(string $segment): ?string
    {
        return preg_match('/^[A-Za-z0-9_\-]+$/', $segment) === 1 ? $segment : null;
    }

    /**
     * JOIN one field's answers.
     *
     * The field name is inlined rather than bound: the executor runs the
     * whole compiled statement through `wpdb::prepare()` with only the
     * WHERE params, so a `%s` left in a JOIN is a placeholder/argument
     * mismatch, not a bound value. {@see sanitizeSegment()} restricts the
     * literal to word characters and dashes, so it can carry neither a
     * quote nor a stray `%`.
     */
    private function answerJoin(string $alias, string $fieldName, ?string $subFieldName): string
    {
        global $wpdb;

        $join = sprintf(
            "LEFT JOIN %sfluentform_entry_details AS %s ON %s.submission_id = s.id AND %s.field_name = '%s'",
            $wpdb->prefix,
            $alias,
            $alias,
            $alias,
            $fieldName,
        );

        if ($subFieldName !== null) {
            $join .= sprintf(" AND %s.sub_field_name = '%s'", $alias, $subFieldName);
        }

        return $join;
    }

    protected function getFrom(Query $query): string
    {
        global $wpdb;

        return $wpdb->prefix . 'fluentform_submissions AS s';
    }

    protected function getJoins(): array
    {
        return $this->joins;
    }

    protected function compileScopeWhere(Query $query): array
    {
        $clauses = [];
        $params = [];

        $formIds = $this->scopeFormIds($query->source->scope);

        if ($formIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($formIds), '%d'));
            $clauses[] = "s.form_id IN ({$placeholders})";
            $params = array_merge($params, $formIds);
        }

        $statuses = $this->scopeStatuses($query->source->scope);

        if ($statuses !== []) {
            $placeholders = implode(', ', array_fill(0, count($statuses), '%s'));
            $clauses[] = "s.status IN ({$placeholders})";
            $params = array_merge($params, $statuses);
        } else {
            $quoted = implode(', ', array_map(
                static fn (string $status): string => "'" . $status . "'",
                self::EXCLUDED_STATUSES,
            ));
            $clauses[] = "s.status NOT IN ({$quoted})";
        }

        return [
            'clause' => implode(' AND ', $clauses),
            'params' => $params,
        ];
    }

    /**
     * @return int[]
     */
    private function scopeFormIds(array $scope): array
    {
        $formIds = $scope['form_ids'] ?? $scope['form_id'] ?? [];

        if (!is_array($formIds)) {
            $formIds = [$formIds];
        }

        $formIds = array_filter(
            array_map('intval', $formIds),
            static fn (int $id): bool => $id > 0,
        );

        return array_values(array_unique($formIds));
    }

    /**
     * Statuses a scope asked for.
     *
     * Not checked against a vocabulary: add-ons extend the status set
     * (approval flows), and rejecting an unlisted-but-real status would
     * silently widen the result set to the default instead. Every value is
     * bound as a `%s` parameter, so an unexpected one yields no rows rather
     * than reaching the SQL string.
     *
     * @return string[]
     */
    private function scopeStatuses(array $scope): array
    {
        $statuses = $scope['status'] ?? [];

        if (!is_array($statuses)) {
            $statuses = [$statuses];
        }

        return array_values(array_map('strval', $statuses));
    }

    /**
     * Search the answer columns rather than every mapped column.
     *
     * The base implementation LIKEs every column in the map, which on this
     * shape means matching a search term against submission IDs and
     * timestamps.
     *
     * @inheritDoc
     */
    protected function compileSearch(string $search, array $columnMap): array
    {
        $escaped = '%' . SqlFilterCompiler::escapeLike($search) . '%';
        $clauses = [];
        $params = [];

        foreach ($columnMap as $key => $colExpr) {
            $searchable = str_ends_with($colExpr, '.field_value')
                || in_array((string) $key, ['ip', 'city', 'country'], true);

            if (!$searchable) {
                continue;
            }

            $clauses[] = "{$colExpr} LIKE %s";
            $params[] = $escaped;
        }

        if ($clauses === []) {
            return ['clause' => '', 'params' => []];
        }

        return [
            'clause' => '(' . implode(' OR ', $clauses) . ')',
            'params' => $params,
        ];
    }

    protected function estimateRowCount(Query $query): ?int
    {
        global $wpdb;

        $formIds = $this->scopeFormIds($query->source->scope);

        if ($formIds === []) {
            return null;
        }

        $table = $wpdb->prefix . 'fluentform_submissions';
        $placeholders = implode(', ', array_fill(0, count($formIds), '%d'));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE form_id IN ({$placeholders}) AND status NOT IN ('trashed', 'spam')",
                ...$formIds,
            ),
        );

        return $count !== null ? (int) $count : null;
    }

    protected function getResultSchema(WpdbCompiledQuery $compiled): array
    {
        $schema = [];

        foreach ($compiled->columnMap as $key => $expr) {
            $key = (string) $key;

            if (in_array($key, self::DATETIME_KEYS, true)) {
                $schema[$key] = ColumnType::Datetime;
            } elseif (str_ends_with($key, '_bucket') && in_array(substr($key, 0, -7), self::DATETIME_KEYS, true)) {
                $schema[$key] = ColumnType::Datetime;
            } elseif (in_array($key, self::NUMERIC_KEYS, true)) {
                $schema[$key] = ColumnType::Float;
            } else {
                $schema[$key] = $this->fieldTypes[$key] ?? ColumnType::String;
            }
        }

        return $schema;
    }

    /**
     * Submission columns to select when browse mode ran without a field
     * map, so the query returns rows rather than a bare COUNT(*).
     *
     * @return string[]
     */
    private function browseFallbackKeys(): array
    {
        return ['id', 'form_id', 'created_at', 'status', 'ip'];
    }

    /**
     * Collect every field key the query references.
     *
     * @return string[]
     */
    private function collectFieldKeys(Query $query, BackendSchema $schema): array
    {
        $keys = [];

        if ($query->type === QueryType::Browse) {
            $keys = $schema->fieldNames();

            if ($keys === []) {
                $keys = $this->browseFallbackKeys();
            }
        }

        foreach ($query->dimensions as $dimension) {
            $keys[] = $dimension->field;
        }

        foreach ($query->metrics as $metric) {
            if ($metric->field !== null) {
                $keys[] = $metric->field;
            }
        }

        if ($query->time !== null) {
            $keys[] = $query->time->field;
        }

        foreach ($query->orderBy as $order) {
            if ($schema->hasField($order->field)) {
                $keys[] = $order->field;
            }
        }

        $this->collectConditionKeys($query->where, $keys);

        return array_values(array_unique($keys));
    }

    /**
     * @param string[] $keys
     */
    private function collectConditionKeys(?object $group, array &$keys): void
    {
        if ($group instanceof ConditionGroup) {
            foreach ($group->conditions as $condition) {
                $this->collectConditionKeys($condition, $keys);
            }
        } elseif ($group instanceof Condition) {
            $keys[] = $group->field;
        }
    }
}
