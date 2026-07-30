<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\GravityForms;

use DataKit\DataViews\Query\Backend\WordPress\AbstractWpdbBackend;
use DataKit\DataViews\Query\Backend\WordPress\WpdbCompiledQuery;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\Source;

/**
 * Gravity Forms query backend.
 *
 * Queries `gf_entry` for system columns, LEFT JOINing `gf_entry_meta` once per
 * referenced form field.
 *
 * @since $ver$
 */
final class GravityFormsBackend extends AbstractWpdbBackend
{
    public const SOURCE_TYPE = 'gravity_forms';

    /**
     * Prefix for per-form field keys.
     *
     * Keeps keys as strings. A bare field ID ("3") would be cast to `int` by
     * PHP the moment it became an array key, and the string helpers in
     * {@see getResultSchema()} throw a TypeError on an int.
     */
    public const FIELD_PREFIX = 'field:';

    /**
     * System columns on `gf_entry` that require no JOIN.
     *
     * `created_at` / `updated_at` are the cross-source semantic names other
     * backends expose, carried here so a spec written against one source still
     * resolves against this one.
     *
     * @var array<string, string>
     */
    private const ENTRY_COLUMNS = [
        'entry_id' => 'id',
        'form_id' => 'form_id',
        'status' => 'status',
        'date_created' => 'date_created',
        'date_updated' => 'date_updated',
        'ip' => 'ip',
        'source_url' => 'source_url',
        'user_agent' => 'user_agent',
        'currency' => 'currency',
        'payment_status' => 'payment_status',
        'payment_date' => 'payment_date',
        'payment_amount' => 'payment_amount',
        'payment_method' => 'payment_method',
        'transaction_id' => 'transaction_id',
        'is_starred' => 'is_starred',
        'is_read' => 'is_read',
        'created_by' => 'created_by',
        'transaction_type' => 'transaction_type',
        'post_id' => 'post_id',
        'is_fulfilled' => 'is_fulfilled',
        'created_at' => 'date_created',
        'updated_at' => 'date_updated',
    ];

    /**
     * Virtual columns resolved by joining another table.
     *
     * @var array<string, array{alias: string, column: string, join: string}>
     */
    private const JOINED_COLUMNS = [
        'form_title' => [
            'alias' => 'gf_form',
            'column' => 'title',
            'join' => 'INNER JOIN %sgf_form AS gf_form ON gf_form.id = e.form_id',
        ],
    ];

    /** @var string[] */
    private const DATETIME_COLUMNS = ['date_created', 'date_updated', 'payment_date', 'created_at', 'updated_at'];

    /** @var string[] */
    private const NUMERIC_COLUMNS = ['payment_amount', 'is_starred', 'is_read', 'created_by', 'entry_id', 'form_id', 'post_id', 'is_fulfilled'];

    /** @var string[] Entry statuses a scope may ask for. */
    private const ALLOWED_STATUSES = ['active', 'spam', 'trash'];

    /** @var string[] Accumulated JOIN clauses for the query being compiled. */
    private array $joins = [];

    /** @var array<string, ColumnType> Column types by field key, captured during compile. */
    private array $fieldTypes = [];

    private FormSchemaProvider $schemaProvider;

    public function __construct(?FormSchemaProvider $schemaProvider = null)
    {
        parent::__construct();

        $this->schemaProvider = $schemaProvider ?? new GravityFormsSchemaProvider();
    }

    public static function isAvailable(): bool
    {
        return class_exists('GFAPI');
    }

    public function sourceType(): string
    {
        return self::SOURCE_TYPE;
    }

    /**
     * Create a source for Gravity Forms entries.
     *
     * @param int[]    $form_ids Form IDs to include.
     * @param string[] $status   Entry statuses (default: ['active']).
     */
    public static function source(array $form_ids, array $status = ['active']): Source
    {
        return new Source(self::SOURCE_TYPE, 'entries', [
            'form_ids' => $form_ids,
            'status' => $status,
        ]);
    }

    /**
     * Create a source for a single form's entries.
     *
     * @param int      $form_id Single form ID.
     * @param string[] $status  Entry statuses (default: ['active']).
     */
    public static function sourceForForm(int $form_id, array $status = ['active']): Source
    {
        return self::source($form_id > 0 ? [$form_id] : [], $status);
    }

    /**
     * {@inheritDoc}
     *
     * @return Capability[]
     */
    public function capabilities(): array
    {
        return [
            // All filter capabilities
            Capability::FilterEq, Capability::FilterNeq,
            Capability::FilterGt, Capability::FilterGte,
            Capability::FilterLt, Capability::FilterLte,
            Capability::FilterIn, Capability::FilterNotIn,
            Capability::FilterBetween,
            Capability::FilterContains, Capability::FilterNotContains,
            Capability::FilterStartsWith,
            Capability::FilterIsEmpty, Capability::FilterIsNotEmpty,
            // All aggregate capabilities
            Capability::AggCount, Capability::AggSum, Capability::AggAvg,
            Capability::AggMin, Capability::AggMax, Capability::AggCountDistinct,
            // Structural
            Capability::GroupBy, Capability::Having, Capability::OrConditions,
            Capability::TimeBucket, Capability::Search, Capability::OrderBy,
            Capability::LimitOffset, Capability::Unnest,
        ];
    }

    /**
     * Schema version for cache key versioning.
     *
     * Past the base version because per-form fields changed both the field set
     * and the key format, so results cached against the entry-columns-only
     * schema must not be served.
     */
    public function schemaVersion(): int
    {
        return 2;
    }

    public function describe(array $scope): BackendSchema
    {
        return $this->schemaProvider->describe($scope);
    }

    protected function buildColumnMap(Query $query, BackendSchema $schema): array
    {
        global $wpdb;

        $this->joins = [];
        $this->fieldTypes = [];

        foreach ($schema->fields as $field) {
            $this->fieldTypes[$field->key] = $field->type;

            foreach ($field->aliases as $alias) {
                $this->fieldTypes[$alias] = $field->type;
            }
        }

        $columnMap = [];
        $addedJoinedTables = [];

        foreach ($this->collectFieldKeys($query, $schema) as $key) {
            $key = (string) $key;

            if (isset(self::ENTRY_COLUMNS[$key])) {
                $columnMap[$key] = 'e.' . self::ENTRY_COLUMNS[$key];
                continue;
            }

            if (isset(self::JOINED_COLUMNS[$key])) {
                $joinDef = self::JOINED_COLUMNS[$key];
                $alias = $joinDef['alias'];
                $columnMap[$key] = "{$alias}.{$joinDef['column']}";

                if (!isset($addedJoinedTables[$alias])) {
                    $this->joins[] = sprintf($joinDef['join'], $wpdb->prefix);
                    $addedJoinedTables[$alias] = true;
                }

                continue;
            }

            $metaKey = $this->metaKeyFor($key);

            if ($metaKey === null) {
                continue;
            }

            $alias = $this->nextJoinAlias();
            $this->joins[] = $this->metaJoin($alias, $metaKey);
            $columnMap[$key] = "{$alias}.meta_value";
        }

        return $columnMap;
    }

    /**
     * Resolve the `gf_entry_meta.meta_key` a field key selects.
     *
     * Returns null for anything that could not be a meta key, so a bogus key
     * never reaches the SQL string.
     */
    private function metaKeyFor(string $key): ?string
    {
        $metaKey = str_starts_with($key, self::FIELD_PREFIX)
            ? substr($key, strlen(self::FIELD_PREFIX))
            : $key;

        // Gravity Forms meta keys are field IDs ("3", "1.3") or add-on slugs.
        return preg_match('/^[A-Za-z0-9_.\-]+$/', $metaKey) === 1 ? $metaKey : null;
    }

    /**
     * JOIN one field's answers.
     *
     * The meta key is inlined rather than bound: the executor runs the whole
     * compiled statement through `wpdb::prepare()` with only the WHERE params,
     * so a `%s` left in a JOIN is a placeholder/argument mismatch, not a bound
     * value. {@see metaKeyFor()} restricts the literal to `[A-Za-z0-9_.-]`, so
     * it can carry neither a quote nor a stray `%`.
     */
    private function metaJoin(string $alias, string $metaKey): string
    {
        global $wpdb;

        return sprintf(
            "LEFT JOIN %sgf_entry_meta AS %s ON %s.entry_id = e.id AND %s.meta_key = '%s'",
            $wpdb->prefix,
            $alias,
            $alias,
            $alias,
            $metaKey,
        );
    }

    protected function getFrom(Query $query): string
    {
        global $wpdb;

        return "{$wpdb->prefix}gf_entry AS e";
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

        $quoted = implode(', ', array_map(
            static fn (string $status): string => "'" . $status . "'",
            $this->scopeStatuses($query->source->scope),
        ));
        $clauses[] = "e.status IN ({$quoted})";

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
     * Entry statuses a scope asked for.
     *
     * Checked against {@see ALLOWED_STATUSES} because the values are inlined:
     * Gravity Forms' status vocabulary is closed, so an unlisted value is a
     * caller error rather than a status this backend has not heard of.
     *
     * @return string[]
     */
    private function scopeStatuses(array $scope): array
    {
        $statuses = $scope['status'] ?? [];

        if (!is_array($statuses)) {
            $statuses = [$statuses];
        }

        $statuses = array_values(array_intersect(
            array_map('strval', $statuses),
            self::ALLOWED_STATUSES,
        ));

        return $statuses === [] ? ['active'] : $statuses;
    }

    protected function estimateRowCount(Query $query): ?int
    {
        global $wpdb;

        $formIds = $this->scopeFormIds($query->source->scope);

        if ($formIds === []) {
            return null;
        }

        $placeholders = implode(', ', array_fill(0, count($formIds), '%d'));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}gf_entry WHERE form_id IN ({$placeholders}) AND status = 'active'",
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

            if (in_array($key, self::DATETIME_COLUMNS, true)) {
                $schema[$key] = ColumnType::Datetime;
            } elseif (str_ends_with($key, '_bucket') && in_array(substr($key, 0, -7), self::DATETIME_COLUMNS, true)) {
                // Time bucket aliases (e.g. created_at_bucket) are datetime-derived.
                $schema[$key] = ColumnType::Datetime;
            } elseif (in_array($key, self::NUMERIC_COLUMNS, true)) {
                $schema[$key] = ColumnType::Float;
            } else {
                $schema[$key] = $this->fieldTypes[$key] ?? ColumnType::String;
            }
        }

        return $schema;
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
