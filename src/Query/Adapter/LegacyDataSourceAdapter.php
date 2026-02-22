<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Adapter;

use DataKit\DataViews\Data\DataSource;
use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Search;
use DataKit\DataViews\DataView\Sort;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\CompiledQuery;
use DataKit\DataViews\Query\Engine\CostEstimate;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Engine\QueryBackend;
use DataKit\DataViews\Query\Engine\Result;
use DataKit\DataViews\Query\Exception\QueryExecutionException;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SortDirection;

/**
 * Wraps an existing SDK DataSource as a QueryBackend for browse queries.
 *
 * Only operators with a bridge to the legacy Operator class can be used.
 * The N+1 get_data_by_id() pattern is protected by the engine's maxLimit.
 *
 * @since $ver$
 */
final class LegacyDataSourceAdapter implements QueryBackend
{
    /** @var Capability[] */
    private readonly array $declaredCapabilities;

    /**
     * @param DataSource   $dataSource           The legacy data source to wrap.
     * @param Capability[] $declaredCapabilities  Capabilities this source honestly supports.
     */
    public function __construct(
        private readonly DataSource $dataSource,
        array $declaredCapabilities = [],
    ) {
        // NotIn maps to Operator::isNone which uses array_intersect — only works
        // for array-valued fields. Exclude from defaults for safety.
        $this->declaredCapabilities = $declaredCapabilities ?: [
            Capability::FilterEq,
            Capability::FilterNeq,
            Capability::FilterIn,
            Capability::Search,
            Capability::OrderBy,
            Capability::LimitOffset,
        ];
    }

    public function sourceType(): string
    {
        return 'legacy_' . $this->dataSource->id();
    }

    public function capabilities(): array
    {
        return $this->declaredCapabilities;
    }

    public function supports(Capability $cap): bool
    {
        return in_array($cap, $this->declaredCapabilities, true);
    }

    public function describe(array $scope): BackendSchema
    {
        $fields = [];
        $bridgeableOps = $this->bridgeableOperators();

        foreach ($this->dataSource->get_fields() as $key => $label) {
            $fields[] = new FieldSchema(
                key: (string) $key,
                label: (string) $label,
                type: ColumnType::String,
                operators: $bridgeableOps,
            );
        }

        return new BackendSchema(
            $this->sourceType(),
            'Legacy: ' . $this->dataSource->id(),
            'Legacy DataSource adapter with limited capabilities.',
            $this->declaredCapabilities,
            $fields,
        );
    }

    public function schemaVersion(): int
    {
        return 1;
    }

    public function estimate(Query $query): ?CostEstimate
    {
        return null;
    }

    public function compile(Query $query, BackendSchema $schema): CompiledQuery
    {
        return new CompiledQuery([
            'query' => $query,
            'schema' => $schema,
        ], 'Legacy DataSource browse');
    }

    public function execute(CompiledQuery $compiled): Result
    {
        $payload = $compiled->getCompiled();
        /** @var Query $query */
        $query = $payload['query'];

        $source = $this->dataSource;

        // Apply filters
        if ($query->where !== null) {
            $filters = $this->conditionGroupToFilters($query->where);

            if ($filters !== null) {
                $source = $source->filter_by($filters);
            }
        }

        // Apply search
        if ($query->search !== null) {
            $source = $source->search_by(Search::from_string($query->search));
        }

        // Apply sort (first orderBy only — legacy Sort supports one field)
        if ($query->orderBy !== []) {
            $order = $query->orderBy[0];
            $sort = $order->direction === SortDirection::Asc
                ? Sort::asc($order->field)
                : Sort::desc($order->field);
            $source = $source->sort_by($sort);
        }

        // Get total count
        $totalCount = $source->count();

        // Get IDs with limit/offset
        $limit = $query->limit?->limit ?? 20;
        $offset = $query->limit?->offset ?? 0;
        $ids = $source->get_data_ids($limit, $offset);

        // Fetch rows (N+1 pattern — protected by engine's maxLimit)
        $rows = [];
        $schema = [];

        foreach ($ids as $id) {
            try {
                $row = $source->get_data_by_id((string) $id);
                $rows[] = $row;

                // Build schema from first row
                if ($schema === []) {
                    foreach ($row as $key => $value) {
                        $schema[(string) $key] = ColumnType::String;
                    }
                }
            } catch (\Throwable $e) {
                // Skip rows that fail to load
                continue;
            }
        }

        return new Result(
            $schema,
            $rows,
            $totalCount,
            limit: $limit,
            offset: $offset,
        );
    }

    /**
     * Converts a ConditionGroup to legacy Filters.
     *
     * Only supports flat AND groups with bridgeable operators.
     * Returns null if the group can't be converted.
     */
    private function conditionGroupToFilters(ConditionGroup $group): ?Filters
    {
        $filters = [];

        foreach ($group->conditions as $condition) {
            if (!$condition instanceof Condition) {
                // Nested groups not supported by legacy Filters
                continue;
            }

            $legacyOp = $condition->operator->toOperator();

            if ($legacyOp === null) {
                // Operator has no legacy equivalent — skip
                continue;
            }

            $filters[] = Filter::from_array([
                'field' => $condition->field,
                'operator' => (string) $legacyOp,
                'value' => $condition->value,
            ]);
        }

        if ($filters === []) {
            return null;
        }

        return Filters::of($filters[0], ...array_slice($filters, 1));
    }

    /**
     * Returns ComparisonOperators that have a bridge to the legacy Operator.
     *
     * @return ComparisonOperator[]
     */
    private function bridgeableOperators(): array
    {
        return array_filter(
            ComparisonOperator::cases(),
            static fn (ComparisonOperator $op) => $op->toOperator() !== null,
        );
    }
}
