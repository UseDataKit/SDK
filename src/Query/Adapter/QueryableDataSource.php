<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Adapter;

use DataKit\DataViews\Data\BaseDataSource;
use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Operator;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Engine\QueryEngine;
use DataKit\DataViews\Query\Engine\Result;
use DataKit\DataViews\Query\Limit;
use DataKit\DataViews\Query\LogicOperator;
use DataKit\DataViews\Query\OrderBy;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SortDirection;
use DataKit\DataViews\Query\Source;

/**
 * Bridges the QueryEngine back into the DataSource interface.
 *
 * Extends BaseDataSource so it can participate in the existing DataView pipeline.
 * Also exposes aggregate() for new query capabilities.
 *
 * When filters contain isAll/isNotAll operators that can't be converted to
 * ComparisonOperator, falls back to the parent DataSource behavior.
 *
 * @since $ver$
 */
abstract class QueryableDataSource extends BaseDataSource
{
    private bool $fallbackMode = false;

    public function __construct(
        private readonly QueryEngine $engine,
        private readonly Source $source,
    ) {
    }

    /**
     * Returns a unique ID for this data source.
     */
    abstract public function id(): string;

    /**
     * Returns field key => label map.
     *
     * @return array<string, string>
     */
    abstract public function get_fields(): array;

    /**
     * @inheritDoc
     */
    public function get_data_ids(int $limit = 20, int $offset = 0): array
    {
        if ($this->fallbackMode) {
            return $this->fallbackGetDataIds($limit, $offset);
        }

        try {
            $query = $this->buildBrowseQuery($limit, $offset);
        } catch (\InvalidArgumentException $e) {
            // isAll/isNotAll conversion failed — fall back to legacy path
            $this->fallbackMode = true;

            return $this->fallbackGetDataIds($limit, $offset);
        }

        $result = $this->engine->execute($query);

        return $this->extractIds($result);
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        if ($this->fallbackMode) {
            return $this->fallbackCount();
        }

        try {
            $query = $this->buildBrowseQuery(1, 0);
        } catch (\InvalidArgumentException $e) {
            $this->fallbackMode = true;

            return $this->fallbackCount();
        }

        $result = $this->engine->execute($query);

        return $result->getTotalCount();
    }

    /**
     * Execute an aggregate query through the engine.
     */
    public function aggregate(Query $query): Result
    {
        return $this->engine->execute($query);
    }

    /**
     * Build a browse Query from the current DataSource state.
     *
     * @throws \InvalidArgumentException If filters contain isAll/isNotAll.
     */
    private function buildBrowseQuery(int $limit, int $offset): Query
    {
        $where = null;

        if ($this->filters !== null) {
            $conditions = [];

            foreach ($this->filters as $filter) {
                $filterArray = $filter->to_array();
                $legacyOp = Operator::try_from($filterArray['operator']);

                if ($legacyOp === null) {
                    continue;
                }

                // This throws \InvalidArgumentException for isAll/isNotAll
                $compOp = ComparisonOperator::fromOperator($legacyOp);
                $conditions[] = new Condition(
                    $filterArray['field'],
                    $compOp,
                    $filterArray['value'],
                );
            }

            if ($conditions !== []) {
                $where = new ConditionGroup(LogicOperator::And, $conditions);
            }
        }

        $orderBy = [];

        if ($this->sort !== null) {
            $sortArray = $this->sort->to_array();
            $orderBy[] = new OrderBy(
                $sortArray['field'],
                $sortArray['direction'] === 'DESC' ? SortDirection::Desc : SortDirection::Asc,
            );
        }

        return new Query(
            source: $this->source,
            type: QueryType::Browse,
            where: $where,
            orderBy: $orderBy,
            limit: new Limit($limit, $offset),
            search: $this->search !== null ? (string) $this->search : null,
        );
    }

    /**
     * Extract IDs from a browse result.
     *
     * Looks for 'id', 'ID', or the first column as fallback.
     *
     * @return string[]
     */
    private function extractIds(Result $result): array
    {
        $ids = [];

        foreach ($result as $row) {
            $id = $row['id'] ?? $row['ID'] ?? reset($row);
            $ids[] = (string) $id;
        }

        return $ids;
    }

    /**
     * Fallback: return empty IDs. Subclasses can override for real fallback behavior.
     *
     * @return string[]
     */
    protected function fallbackGetDataIds(int $limit, int $offset): array
    {
        return [];
    }

    /**
     * Fallback: return 0 count. Subclasses can override for real fallback behavior.
     */
    protected function fallbackCount(): int
    {
        return 0;
    }
}
