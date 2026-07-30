<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\WPForms;

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
 * WPForms query backend.
 *
 * Queries `wpforms_entries` for system columns, LEFT JOINing
 * `wpforms_entry_fields` once per referenced form field and
 * `wpforms_entry_meta` once per referenced quiz field.
 *
 * Entries are a WPForms Pro table — Lite stores no submissions at all — so
 * {@see isAvailable()} gates on the table rather than on the plugin.
 *
 * @since $ver$
 */
final class WPFormsBackend extends AbstractWpdbBackend
{
    public const SOURCE_TYPE = 'wpforms';

    /**
     * Prefix for per-form field keys.
     *
     * Keeps keys as strings. A bare numeric key ("3") would be cast to `int`
     * by PHP the moment it became an array key, and the string helpers in
     * {@see getResultSchema()} throw a TypeError on an int.
     */
    public const FIELD_PREFIX = 'field:';

    /**
     * Field keys mapped to their `wpforms_entries` column.
     *
     * `created_at` / `updated_at` are the cross-source semantic names other
     * backends expose, carried here so a spec written against one source still
     * resolves against this one.
     *
     * @var array<string, string>
     */
    private const ENTRY_COLUMNS = [
        'entry_id' => 'entry_id',
        'form_id' => 'form_id',
        'post_id' => 'post_id',
        'user_id' => 'user_id',
        'status' => 'status',
        'type' => 'type',
        'viewed' => 'viewed',
        'starred' => 'starred',
        'date' => 'date',
        'date_modified' => 'date_modified',
        'ip_address' => 'ip_address',
        'user_agent' => 'user_agent',
        'user_uuid' => 'user_uuid',
        'created_at' => 'date',
        'updated_at' => 'date_modified',
    ];

    /**
     * Quiz addon answers, stored in `wpforms_entry_meta` keyed by `type`.
     *
     * @var array<string, string>
     */
    private const QUIZ_META_TYPES = [
        'quiz_outcome' => 'quiz_outcome',
        'quiz_type' => 'quiz_type',
        'quiz_completion' => 'quiz_completion',
    ];

    /**
     * Maximum EAV JOINs per query.
     *
     * Each referenced form field costs one LEFT JOIN against
     * `wpforms_entry_fields`, and MySQL's planner degrades sharply past a few
     * dozen. Raise this only with a measured plan on a wide form; lower it if
     * wide-form queries time out.
     */
    private const MAX_FIELD_JOINS = 40;

    /** @var string[] Statuses excluded when a scope names none. */
    private const EXCLUDED_STATUSES = ['trash', 'spam'];

    /** @var string[] */
    private const DATETIME_KEYS = ['date', 'date_modified', 'created_at', 'updated_at'];

    /** @var string[] */
    private const NUMERIC_KEYS = ['entry_id', 'form_id', 'post_id', 'user_id', 'viewed', 'starred'];

    /**
     * WPForms field types that carry no answer.
     *
     * @var string[]
     */
    private const EXCLUDED_FIELD_TYPES = [
        'html', 'content', 'pagebreak', 'divider', 'section', 'layout',
        'captcha', 'turnstile', 'hcaptcha', 'internal-information', 'entry-preview',
    ];

    /** @var string[] Accumulated JOIN clauses for the query being compiled. */
    private array $joins = [];

    /** @var array<string, ColumnType> Column types by field key, captured during compile. */
    private array $fieldTypes = [];

    /** Separate counter so quiz-meta aliases cannot collide with field aliases. */
    private int $metaJoinCounter = 0;

    private WPFormsFormRepository $forms;

    public function __construct(?WPFormsFormRepository $forms = null)
    {
        parent::__construct();

        $this->forms = $forms ?? new WPFormsApiFormRepository();
    }

    /**
     * Whether WPForms entries can be queried here.
     *
     * WPForms Lite ships no `wpforms_entries` table: it stores nothing. So an
     * active plugin is not enough, and this checks the table itself, which
     * also covers a host that populates those tables without Pro. On a Lite
     * site the backend stays unregistered, and the engine answers a WPForms
     * query with "No backend registered for source type wpforms" — an error a
     * caller can act on, where a registered backend over a missing table would
     * return zero rows that read as "no submissions yet".
     */
    public static function isAvailable(): bool
    {
        global $wpdb;

        if (!isset($wpdb) || !function_exists('wpforms')) {
            return false;
        }

        static $exists = null;

        if ($exists === null) {
            $table = $wpdb->prefix . 'wpforms_entries';

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
     * Create a source for WPForms entries.
     *
     * @param int[]    $formIds Form IDs to include; empty means all forms.
     * @param string[] $status  Entry statuses; empty excludes trash and spam.
     */
    public static function source(array $formIds = [], array $status = []): Source
    {
        return new Source(self::SOURCE_TYPE, 'entries', [
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
            'WPForms Entries',
            'WPForms submissions with per-form answers and quiz metadata.',
            $this->capabilities(),
            $fields,
        );
    }

    /**
     * Columns present on `wpforms_entries` for every form.
     *
     * @return FieldSchema[]
     */
    private function systemFieldSchemas(): array
    {
        $allOps = ComparisonOperator::cases();

        return [
            new FieldSchema('entry_id', 'Entry ID', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('form_id', 'Form ID', ColumnType::Integer, $allOps),
            new FieldSchema('post_id', 'Page ID', ColumnType::Integer, $allOps),
            new FieldSchema('user_id', 'User ID', ColumnType::Integer, $allOps),
            new FieldSchema(
                'status',
                'Status',
                ColumnType::String,
                $allOps,
                // The three statuses GravityDash's WPForms field mapper already
                // offers. WPForms addons add more (partial, abandoned); those
                // filter fine, they just aren't advertised as choices.
                enumValues: [
                    '' => 'Completed',
                    'spam' => 'Spam',
                    'trash' => 'Trash',
                ],
            ),
            new FieldSchema('type', 'Type', ColumnType::String, $allOps),
            new FieldSchema('viewed', 'Viewed', ColumnType::Integer, $allOps, enumValues: ['0' => 'No', '1' => 'Yes']),
            new FieldSchema('starred', 'Starred', ColumnType::Integer, $allOps, enumValues: ['0' => 'No', '1' => 'Yes']),
            new FieldSchema(
                'date',
                'Date',
                ColumnType::Datetime,
                $allOps,
                aggregatable: true,
                timezone: 'utc',
                aliases: ['created_at'],
            ),
            new FieldSchema(
                'date_modified',
                'Date Modified',
                ColumnType::Datetime,
                $allOps,
                aggregatable: true,
                timezone: 'utc',
                aliases: ['updated_at'],
            ),
            new FieldSchema('ip_address', 'IP Address', ColumnType::String, $allOps),
            new FieldSchema('user_agent', 'User Agent', ColumnType::String, $allOps, sortable: false),
            new FieldSchema('user_uuid', 'User UUID', ColumnType::String, $allOps, sortable: false),
        ];
    }

    /**
     * Discover the answerable fields on one form.
     *
     * @return FieldSchema[]
     */
    private function discoverFormFields(int $formId): array
    {
        $form = $this->forms->getForm($formId);

        if ($form === null) {
            return [];
        }

        $allOps = ComparisonOperator::cases();
        $fields = [];
        $definitions = is_array($form['fields'] ?? null) ? $form['fields'] : [];

        foreach ($definitions as $definition) {
            if (!is_array($definition)) {
                continue;
            }

            $fieldId = isset($definition['id']) ? (string) $definition['id'] : '';
            $fieldType = isset($definition['type']) ? (string) $definition['type'] : '';

            if ($fieldId === '' || in_array($fieldType, self::EXCLUDED_FIELD_TYPES, true)) {
                continue;
            }

            $columnType = $this->inferColumnType($fieldType);
            $label = isset($definition['label']) && $definition['label'] !== ''
                ? (string) $definition['label']
                : 'Field ' . $fieldId;

            $fields[] = new FieldSchema(
                self::FIELD_PREFIX . $fieldId,
                $label,
                $columnType,
                $allOps,
                aggregatable: in_array($columnType, [ColumnType::Integer, ColumnType::Float], true),
                description: "Form field: {$fieldType}",
            );
        }

        if ($this->isQuizEnabled($form)) {
            foreach (self::QUIZ_META_TYPES as $key => $_type) {
                $fields[] = new FieldSchema(
                    $key,
                    ucwords(str_replace('_', ' ', $key)),
                    ColumnType::String,
                    $allOps,
                );
            }
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $form
     */
    private function isQuizEnabled(array $form): bool
    {
        $settings = is_array($form['settings'] ?? null) ? $form['settings'] : [];

        return !empty($settings['quiz_enable']);
    }

    private function inferColumnType(string $fieldType): ColumnType
    {
        return match ($fieldType) {
            'number', 'number-slider', 'rating', 'net_promoter_score' => ColumnType::Float,
            'payment-single', 'payment-multiple', 'payment-checkbox',
            'payment-dropdown', 'payment-total' => ColumnType::Float,
            'date-time' => ColumnType::Datetime,
            default => ColumnType::String,
        };
    }

    protected function buildColumnMap(Query $query, BackendSchema $schema): array
    {
        $this->joins = [];
        $this->fieldTypes = [];
        $this->metaJoinCounter = 0;

        foreach ($schema->fields as $field) {
            $this->fieldTypes[$field->key] = $field->type;

            foreach ($field->aliases as $alias) {
                $this->fieldTypes[$alias] = $field->type;
            }
        }

        $columnMap = [];

        foreach ($this->collectFieldKeys($query, $schema) as $key) {
            $key = (string) $key;

            if (isset(self::ENTRY_COLUMNS[$key])) {
                $columnMap[$key] = 'e.' . self::ENTRY_COLUMNS[$key];
                continue;
            }

            if (isset(self::QUIZ_META_TYPES[$key])) {
                $alias = 'em' . ++$this->metaJoinCounter;
                $this->joins[] = $this->quizMetaJoin($alias, self::QUIZ_META_TYPES[$key]);
                $columnMap[$key] = $alias . '.data';
                continue;
            }

            $fieldId = $this->fieldIdFor($key);

            if ($fieldId === null || $this->joinCounter >= self::MAX_FIELD_JOINS) {
                continue;
            }

            $alias = $this->nextJoinAlias('f');
            $this->joins[] = $this->answerJoin($alias, $fieldId);
            $columnMap[$key] = $alias . '.value';
        }

        return $columnMap;
    }

    /**
     * Resolve the `wpforms_entry_fields.field_id` a field key selects.
     *
     * Returns null for anything that could not be a WPForms field ID, so a
     * bogus key never reaches the SQL string.
     */
    private function fieldIdFor(string $key): ?string
    {
        $fieldId = str_starts_with($key, self::FIELD_PREFIX)
            ? substr($key, strlen(self::FIELD_PREFIX))
            : $key;

        // WPForms field IDs are a field index, optionally with a repeater
        // sub-index ("3", "3.0"). The column is a varchar, so the value is
        // inlined rather than cast, and this is what keeps it safe.
        return preg_match('/^\d+(\.\d+)*$/', $fieldId) === 1 ? $fieldId : null;
    }

    /**
     * JOIN one field's answers.
     *
     * The field ID is inlined rather than bound: the executor runs the whole
     * compiled statement through `wpdb::prepare()` with only the WHERE params,
     * so a `%s` left in a JOIN is a placeholder/argument mismatch, not a bound
     * value. {@see fieldIdFor()} restricts the literal to digits and dots, so
     * it can carry neither a quote nor a stray `%`.
     */
    private function answerJoin(string $alias, string $fieldId): string
    {
        global $wpdb;

        return sprintf(
            "LEFT JOIN %swpforms_entry_fields AS %s ON %s.entry_id = e.entry_id AND %s.field_id = '%s'",
            $wpdb->prefix,
            $alias,
            $alias,
            $alias,
            $fieldId,
        );
    }

    /**
     * JOIN one quiz answer. The type comes from {@see QUIZ_META_TYPES}, so the
     * inlined literal is a class constant rather than caller input.
     */
    private function quizMetaJoin(string $alias, string $metaType): string
    {
        global $wpdb;

        return sprintf(
            "LEFT JOIN %swpforms_entry_meta AS %s ON %s.entry_id = e.entry_id AND %s.type = '%s'",
            $wpdb->prefix,
            $alias,
            $alias,
            $alias,
            $metaType,
        );
    }

    protected function getFrom(Query $query): string
    {
        global $wpdb;

        return $wpdb->prefix . 'wpforms_entries AS e';
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
            $clauses[] = "e.form_id IN ({$placeholders})";
            $params = array_merge($params, $formIds);
        }

        $statuses = $this->scopeStatuses($query->source->scope);

        if ($statuses !== []) {
            $placeholders = implode(', ', array_fill(0, count($statuses), '%s'));
            $clauses[] = "e.status IN ({$placeholders})";
            $params = array_merge($params, $statuses);
        } else {
            $quoted = implode(', ', array_map(
                static fn (string $status): string => "'" . $status . "'",
                self::EXCLUDED_STATUSES,
            ));
            $clauses[] = "e.status NOT IN ({$quoted})";
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
     * Not checked against a vocabulary: WPForms' status set is not fully
     * pinned down here, and rejecting an unlisted-but-real status would
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
     * shape means matching a search term against entry IDs and timestamps.
     *
     * @inheritDoc
     */
    protected function compileSearch(string $search, array $columnMap): array
    {
        $escaped = '%' . SqlFilterCompiler::escapeLike($search) . '%';
        $clauses = [];
        $params = [];

        foreach ($columnMap as $key => $colExpr) {
            $searchable = str_ends_with($colExpr, '.value')
                || str_ends_with($colExpr, '.data')
                || (string) $key === 'ip_address';

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

        $table = $wpdb->prefix . 'wpforms_entries';
        $placeholders = implode(', ', array_fill(0, count($formIds), '%d'));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE form_id IN ({$placeholders}) AND status NOT IN ('trash', 'spam')",
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
     * Entry columns to select when browse mode ran without a field map, so the
     * query returns rows rather than a bare COUNT(*).
     *
     * @return string[]
     */
    private function browseFallbackKeys(): array
    {
        return ['entry_id', 'form_id', 'date', 'status', 'ip_address'];
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
