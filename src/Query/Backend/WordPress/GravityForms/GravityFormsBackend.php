<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\GravityForms;

use DataKit\DataViews\Query\Backend\WordPress\AbstractWpdbBackend;
use DataKit\DataViews\Query\Backend\WordPress\WpdbCompiledQuery;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Query;

/**
 * Gravity Forms query backend.
 *
 * Queries wp_gf_entry with LEFT JOIN wp_gf_entry_meta per field.
 *
 * @since $ver$
 */
final class GravityFormsBackend extends AbstractWpdbBackend
{
    /**
     * System columns on wp_gf_entry that don't require JOINs.
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
    ];

    private const DATETIME_COLUMNS = ['date_created', 'date_updated', 'payment_date'];
    private const NUMERIC_COLUMNS = ['payment_amount', 'is_starred', 'is_read', 'created_by', 'entry_id', 'form_id'];

    /** @var string[] Accumulated JOIN clauses during column map building. */
    private array $joins = [];

    /** @var GravityFormsSchemaProvider|null Cached schema provider. */
    private ?GravityFormsSchemaProvider $schemaProvider;

    public function __construct(
        ?GravityFormsSchemaProvider $schemaProvider = null,
    ) {
        parent::__construct();
        $this->schemaProvider = $schemaProvider;
    }

    public function sourceType(): string
    {
        return 'gravity_forms';
    }

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

    public function describe(array $scope): BackendSchema
    {
        if ($this->schemaProvider !== null) {
            return $this->schemaProvider->describe($scope);
        }

        return $this->describeDefault($scope);
    }

    protected function buildColumnMap(Query $query, BackendSchema $schema): array
    {
        $this->joins = [];
        $columnMap = [];

        // Collect all field keys referenced in the query
        $fieldKeys = $this->collectFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            if (isset(self::ENTRY_COLUMNS[$key])) {
                $columnMap[$key] = 'e.' . self::ENTRY_COLUMNS[$key];
            } else {
                // Meta field — add a JOIN
                $alias = $this->nextJoinAlias();
                $this->addMetaJoin($alias, $key);
                $columnMap[$key] = "{$alias}.meta_value";
            }
        }

        return $columnMap;
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
        global $wpdb;

        $clauses = [];
        $params = [];

        // Form ID scope
        $formIds = $query->source->scope['form_id'] ?? $query->source->scope['form_ids'] ?? [];

        if (is_array($formIds) && $formIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($formIds), '%d'));
            $clauses[] = "e.form_id IN ({$placeholders})";
            $params = array_merge($params, array_map('intval', $formIds));
        } elseif (is_numeric($formIds)) {
            $clauses[] = 'e.form_id = %d';
            $params[] = (int) $formIds;
        }

        // Default status filter (exclude trash)
        $clauses[] = "e.status IN ('active')";

        return [
            'clause' => implode(' AND ', $clauses),
            'params' => $params,
        ];
    }

    protected function estimateRowCount(Query $query): ?int
    {
        global $wpdb;

        $formIds = $query->source->scope['form_id'] ?? $query->source->scope['form_ids'] ?? [];

        if (!is_array($formIds) || $formIds === []) {
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
            if (in_array($key, self::DATETIME_COLUMNS, true)) {
                $schema[$key] = ColumnType::Datetime;
            } elseif (in_array($key, self::NUMERIC_COLUMNS, true)) {
                $schema[$key] = ColumnType::Float;
            } else {
                $schema[$key] = ColumnType::String;
            }
        }

        return $schema;
    }

    private function addMetaJoin(string $alias, string $metaKey): void
    {
        global $wpdb;

        $this->joins[] = sprintf(
            "LEFT JOIN {$wpdb->prefix}gf_entry_meta AS %s ON %s.entry_id = e.id AND %s.meta_key = %%s",
            $alias,
            $alias,
            $alias,
        );
        // Note: The meta_key parameter will be collected and added to params.
        // We store it as a prepared statement placeholder pattern. The actual binding
        // happens through the WpdbCompiledQuery params.
    }

    /**
     * Collect all unique field keys referenced by the query.
     */
    private function collectFieldKeys(Query $query, BackendSchema $schema): array
    {
        $keys = [];

        foreach ($query->dimensions as $dim) {
            $keys[] = $dim->field;
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

        // Collect from conditions
        $this->collectConditionKeys($query->where, $keys);

        return array_unique($keys);
    }

    private function collectConditionKeys(?object $group, array &$keys): void
    {
        if ($group === null) {
            return;
        }

        if ($group instanceof \DataKit\DataViews\Query\ConditionGroup) {
            foreach ($group->conditions as $condition) {
                $this->collectConditionKeys($condition, $keys);
            }
        } elseif ($group instanceof \DataKit\DataViews\Query\Condition) {
            $keys[] = $group->field;
        }
    }

    /**
     * Default schema when no provider is configured.
     */
    private function describeDefault(array $scope): BackendSchema
    {
        $allOps = ComparisonOperator::cases();

        $fields = [];

        foreach (self::ENTRY_COLUMNS as $key => $column) {
            $type = match (true) {
                in_array($key, self::DATETIME_COLUMNS, true) => ColumnType::Datetime,
                in_array($key, self::NUMERIC_COLUMNS, true) => ColumnType::Float,
                default => ColumnType::String,
            };

            $fields[] = new FieldSchema(
                $key,
                ucfirst(str_replace('_', ' ', $key)),
                $type,
                $allOps,
                aggregatable: $type !== ColumnType::String,
                timezone: $type === ColumnType::Datetime ? 'utc' : null,
            );
        }

        return new BackendSchema(
            'gravity_forms',
            'Gravity Forms Entries',
            'Form submissions including payment data, metadata, and user-submitted fields.',
            $this->capabilities(),
            $fields,
        );
    }
}
