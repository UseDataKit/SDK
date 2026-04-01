<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\WSForm;

use DataKit\DataViews\Query\Backend\WordPress\AbstractWpdbBackend;
use DataKit\DataViews\Query\Backend\WordPress\SqlFilterCompiler;
use DataKit\DataViews\Query\Backend\WordPress\WpdbCompiledQuery;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;

/**
 * WS Form query backend.
 *
 * Queries wsf_submit with LEFT JOIN wsf_submit_meta per form field.
 *
 * @since $ver$
 */
final class WSFormBackend extends AbstractWpdbBackend
{
    /**
     * System columns on wsf_submit that don't require JOINs.
     */
    private const SUBMIT_COLUMNS = [
        'submit_id'    => 'id',
        'form_id'      => 'form_id',
        'date_created' => 'date_added',
        'date_updated' => 'date_updated',
        'user_id'      => 'user_id',
        'status'       => 'status',
        'hash'         => 'hash',
        'duration'     => 'duration',
        'count_submit' => 'count_submit',
        'starred'      => 'starred',
        'viewed'       => 'viewed',
        // Semantic aliases used by template specs.
        'created_at'   => 'date_added',
        'updated_at'   => 'date_updated',
    ];

    private const DATETIME_COLUMNS = ['date_created', 'date_updated', 'created_at', 'updated_at'];
    private const NUMERIC_COLUMNS = ['submit_id', 'form_id', 'user_id', 'duration', 'count_submit', 'starred', 'viewed'];

    /** @var string[] Accumulated JOIN clauses during column map building. */
    private array $joins = [];

    public static function isAvailable(): bool
    {
        return class_exists( 'WS_Form_Common' );
    }

    public function sourceType(): string
    {
        return 'ws_form';
    }

    public function capabilities(): array
    {
        return [
            // All filter capabilities.
            Capability::FilterEq, Capability::FilterNeq,
            Capability::FilterGt, Capability::FilterGte,
            Capability::FilterLt, Capability::FilterLte,
            Capability::FilterIn, Capability::FilterNotIn,
            Capability::FilterBetween,
            Capability::FilterContains, Capability::FilterNotContains,
            Capability::FilterStartsWith,
            Capability::FilterIsEmpty, Capability::FilterIsNotEmpty,
            // All aggregate capabilities.
            Capability::AggCount, Capability::AggSum, Capability::AggAvg,
            Capability::AggMin, Capability::AggMax, Capability::AggCountDistinct,
            // Structural.
            Capability::GroupBy, Capability::Having, Capability::OrConditions,
            Capability::TimeBucket, Capability::Search, Capability::OrderBy,
            Capability::LimitOffset,
        ];
    }

    public function describe(array $scope): BackendSchema
    {
        $fields = $this->getSystemFieldSchemas();

        // Discover form-specific fields when form_ids are in scope.
        $formIds = $scope['form_id'] ?? $scope['form_ids'] ?? [];
        if (!is_array($formIds)) {
            $formIds = [$formIds];
        }

        foreach ($formIds as $formId) {
            $formFields = $this->discoverFormFields((int) $formId);
            $fields = array_merge($fields, $formFields);
        }

        return new BackendSchema(
            'ws_form',
            'WS Form Submissions',
            'WS Form submissions with form field metadata.',
            $this->capabilities(),
            $fields,
        );
    }

    /**
     * System columns on wsf_submit that are always available.
     *
     * @return FieldSchema[]
     */
    private function getSystemFieldSchemas(): array
    {
        $allOps = ComparisonOperator::cases();

        return [
            new FieldSchema('submit_id', 'Submission ID', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('form_id', 'Form ID', ColumnType::Integer, $allOps),
            new FieldSchema('date_created', 'Date Created', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc', aliases: ['created_at'],
            ),
            new FieldSchema('date_updated', 'Date Updated', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc', aliases: ['updated_at'],
            ),
            new FieldSchema('user_id', 'User ID', ColumnType::Integer, $allOps),
            new FieldSchema('status', 'Status', ColumnType::String, $allOps,
                enumValues: [
                    'publish' => 'Published',
                    'draft'   => 'Draft',
                    'trash'   => 'Trash',
                ],
            ),
            new FieldSchema('hash', 'Hash', ColumnType::String, $allOps, sortable: false),
            new FieldSchema('duration', 'Duration', ColumnType::Float, $allOps, aggregatable: true,
                description: 'Time taken to complete the form in seconds.',
            ),
            new FieldSchema('count_submit', 'Submit Count', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('starred', 'Starred', ColumnType::Integer, $allOps,
                enumValues: ['0' => 'No', '1' => 'Yes'],
            ),
            new FieldSchema('viewed', 'Viewed', ColumnType::Integer, $allOps,
                enumValues: ['0' => 'No', '1' => 'Yes'],
            ),
        ];
    }

    /**
     * Discover form-specific fields from the wsf_field table.
     *
     * @param int $formId WS Form form ID.
     *
     * @return FieldSchema[]
     */
    private function discoverFormFields(int $formId): array
    {
        global $wpdb;

        $prefix = $wpdb->prefix . 'wsf_';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT f.id AS field_id, f.label, f.type
                FROM {$prefix}field AS f
                INNER JOIN {$prefix}section AS sec ON f.section_id = sec.id
                INNER JOIN {$prefix}group AS g ON sec.group_id = g.id
                WHERE g.form_id = %d
                ORDER BY g.sort_index, sec.sort_index, f.sort_index",
                $formId
            ),
            ARRAY_A
        );

        if (empty($rows)) {
            return [];
        }

        $allOps = ComparisonOperator::cases();
        $fields = [];
        $excludedTypes = [
            'html', 'divider', 'spacer', 'message', 'note',
            'submit', 'save', 'reset', 'clear',
            'tab_previous', 'tab_next', 'button',
            'section_add', 'section_delete', 'section_up', 'section_down', 'section_icons',
            'recaptcha', 'hcaptcha', 'turnstile',
            'progress', 'meter', 'googleroute',
        ];

        foreach ($rows as $row) {
            $fieldType = $row['type'] ?? '';
            $fieldId = (string) ($row['field_id'] ?? '');
            $label = $row['label'] ?? 'Field ' . $fieldId;

            if ($fieldId === '' || in_array($fieldType, $excludedTypes, true)) {
                continue;
            }

            $columnType = $this->inferColumnType($fieldType);
            $aggregatable = in_array($columnType, [ColumnType::Integer, ColumnType::Float], true);

            // Prefix with "field:" to prevent PHP int-casting numeric string keys.
            // buildColumnMap strips this prefix when building the meta JOIN.
            $fields[] = new FieldSchema(
                'field:' . $fieldId,
                $label,
                $columnType,
                $allOps,
                aggregatable: $aggregatable,
                description: "Form field: {$fieldType}",
            );
        }

        return $fields;
    }

    /**
     * Infer the ColumnType for a WS Form field type.
     */
    private function inferColumnType(string $fieldType): ColumnType
    {
        return match ($fieldType) {
            'number', 'range', 'rating', 'quantity' => ColumnType::Float,
            'price', 'price_select', 'price_checkbox', 'price_radio',
            'price_range', 'price_subtotal', 'cart_price', 'cart_total' => ColumnType::Float,
            'datetime' => ColumnType::Datetime,
            default => ColumnType::String,
        };
    }

    protected function buildColumnMap(Query $query, BackendSchema $schema): array
    {
        $this->joins = [];
        $columnMap = [];

        $fieldKeys = $this->collectFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            $key = (string) $key;

            if (isset(self::SUBMIT_COLUMNS[$key])) {
                $columnMap[$key] = 's.' . self::SUBMIT_COLUMNS[$key];
            } elseif (str_starts_with($key, 'field:')) {
                // Form field with "field:" prefix — strip prefix for the meta JOIN.
                $numericId = substr($key, 6);
                $alias = $this->nextJoinAlias();
                $this->addMetaJoin($alias, $numericId);
                $columnMap[$key] = "{$alias}.meta_value";
            } elseif (ctype_digit($key)) {
                // Legacy bare numeric field ID (from query dimensions/filters).
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

        return "{$wpdb->prefix}wsf_submit AS s";
    }

    protected function getJoins(): array
    {
        return $this->joins;
    }

    protected function compileScopeWhere(Query $query): array
    {
        $clauses = [];
        $params = [];

        // Form ID scope.
        $formIds = $query->source->scope['form_id'] ?? $query->source->scope['form_ids'] ?? [];

        if (is_array($formIds) && $formIds !== []) {
            $placeholders = implode(', ', array_fill(0, count($formIds), '%d'));
            $clauses[] = "s.form_id IN ({$placeholders})";
            $params = array_merge($params, array_map('intval', $formIds));
        } elseif (is_numeric($formIds)) {
            $clauses[] = 's.form_id = %d';
            $params[] = (int) $formIds;
        }

        // Status filtering with cross-plugin normalization.
        // WS Form uses 'publish' for submitted, 'draft' for in-progress.
        // Normalize GF's 'active' to WS Form's 'publish'.
        $scopeStatuses = $query->source->scope['status'] ?? [];
        if (is_array($scopeStatuses) && $scopeStatuses !== []) {
            $normalized = array_map(static fn(string $s) => $s === 'active' ? 'publish' : $s, $scopeStatuses);
            $placeholders = implode(', ', array_fill(0, count($normalized), '%s'));
            $clauses[] = "s.status IN ({$placeholders})";
            $params = array_merge($params, $normalized);
        } else {
            $clauses[] = "s.status NOT IN ('trash')";
        }

        return [
            'clause' => implode(' AND ', $clauses),
            'params' => $params,
        ];
    }

    /**
     * Search meta_value columns and hash.
     *
     * @inheritDoc
     */
    protected function compileSearch(string $search, array $columnMap): array
    {
        $escaped = '%' . SqlFilterCompiler::escapeLike($search) . '%';
        $clauses = [];
        $params = [];

        foreach ($columnMap as $key => $colExpr) {
            if (str_contains($colExpr, 'meta_value') || $key === 'hash') {
                $clauses[] = "{$colExpr} LIKE %s";
                $params[] = $escaped;
            }
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

        $table = $wpdb->prefix . 'wsf_submit';
        $formIds = $query->source->scope['form_id'] ?? $query->source->scope['form_ids'] ?? [];

        if (!is_array($formIds) || $formIds === []) {
            return null;
        }

        $placeholders = implode(', ', array_fill(0, count($formIds), '%d'));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE form_id IN ({$placeholders}) AND status NOT IN ('trash')",
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
                $schema[$key] = ColumnType::Datetime;
            } elseif (in_array($key, self::NUMERIC_COLUMNS, true)) {
                $schema[$key] = ColumnType::Float;
            } else {
                $schema[$key] = ColumnType::String;
            }
        }

        return $schema;
    }

    private function addMetaJoin(string $alias, string $fieldId): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wsf_submit_meta';

        $this->joins[] = "LEFT JOIN {$table} AS {$alias} ON {$alias}.parent_id = s.id AND {$alias}.field_id = " . intval($fieldId);
    }

    /**
     * Collect all unique field keys referenced by the query.
     */
    private function collectFieldKeys(Query $query, BackendSchema $schema): array
    {
        // Browse mode: select all available fields from the schema.
        if ($query->type === QueryType::Browse) {
            $keys = $schema->fieldNames();

            if ($query->time !== null) {
                $keys[] = $query->time->field;
            }
            foreach ($query->orderBy as $order) {
                if ($schema->hasField($order->field)) {
                    $keys[] = $order->field;
                }
            }
            $this->collectConditionKeys($query->where, $keys);

            return array_unique($keys);
        }

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
}
