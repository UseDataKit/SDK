<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Adapter;

use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Search;
use DataKit\DataViews\DataView\Sort;
use DataKit\DataViews\Query\Adapter\QueryableDataSource;
use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\Backend\ArrayBackend;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\Engine\BackendRegistry;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Engine\QueryEngine;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\Source;
use PHPUnit\Framework\TestCase;

/**
 * Tests for QueryableDataSource.
 */
final class QueryableDataSourceTest extends TestCase
{
    private ConcreteQueryableDataSource $dataSource;
    private QueryEngine $engine;

    protected function setUp(): void
    {
        $data = [
            'row1' => ['id' => '1', 'name' => 'Alice', 'status' => 'active', 'amount' => 150],
            'row2' => ['id' => '2', 'name' => 'Bob', 'status' => 'inactive', 'amount' => 200],
            'row3' => ['id' => '3', 'name' => 'Charlie', 'status' => 'active', 'amount' => 300],
            'row4' => ['id' => '4', 'name' => 'Diana', 'status' => 'pending', 'amount' => 100],
        ];

        $fieldSchemas = [
            new FieldSchema('id', 'ID', ColumnType::String),
            new FieldSchema('name', 'Name', ColumnType::String),
            new FieldSchema('status', 'Status', ColumnType::String),
            new FieldSchema('amount', 'Amount', ColumnType::Float),
        ];

        $backend = new ArrayBackend('test_queryable', $data, $fieldSchemas);
        $registry = new BackendRegistry();
        $registry->register($backend);

        $this->engine = new QueryEngine($registry);
        $this->dataSource = new ConcreteQueryableDataSource(
            $this->engine,
            new Source('test_queryable'),
        );
    }

    public function test_id(): void
    {
        self::assertSame('test_queryable_source', $this->dataSource->id());
    }

    public function test_get_fields(): void
    {
        $fields = $this->dataSource->get_fields();
        self::assertArrayHasKey('name', $fields);
        self::assertArrayHasKey('status', $fields);
    }

    public function test_get_data_ids(): void
    {
        $ids = $this->dataSource->get_data_ids(10, 0);
        self::assertCount(4, $ids);
    }

    public function test_get_data_ids_with_limit(): void
    {
        $ids = $this->dataSource->get_data_ids(2, 0);
        self::assertCount(2, $ids);
    }

    public function test_count(): void
    {
        self::assertSame(4, $this->dataSource->count());
    }

    public function test_filter_by_eq(): void
    {
        $filtered = $this->dataSource->filter_by(
            Filters::of(Filter::is('status', 'active')),
        );

        $ids = $filtered->get_data_ids(10, 0);
        self::assertCount(2, $ids);
    }

    public function test_filter_by_is_not(): void
    {
        $filtered = $this->dataSource->filter_by(
            Filters::of(Filter::isNot('status', 'active')),
        );

        $ids = $filtered->get_data_ids(10, 0);
        self::assertCount(2, $ids);
    }

    public function test_filter_by_is_any(): void
    {
        $filtered = $this->dataSource->filter_by(
            Filters::of(Filter::isAny('status', ['active', 'pending'])),
        );

        $ids = $filtered->get_data_ids(10, 0);
        self::assertCount(3, $ids);
    }

    public function test_search_by(): void
    {
        $searched = $this->dataSource->search_by(Search::from_string('Alice'));

        $ids = $searched->get_data_ids(10, 0);
        self::assertGreaterThanOrEqual(1, count($ids));
    }

    public function test_sort_by(): void
    {
        $sorted = $this->dataSource->sort_by(Sort::desc('name'));

        $ids = $sorted->get_data_ids(10, 0);
        // Should return IDs; exact order depends on backend
        self::assertNotEmpty($ids);
    }

    public function test_count_with_filter(): void
    {
        $filtered = $this->dataSource->filter_by(
            Filters::of(Filter::is('status', 'active')),
        );

        self::assertSame(2, $filtered->count());
    }

    public function test_aggregate(): void
    {
        $query = new Query(
            source: new Source('test_queryable'),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('status')],
            metrics: [new AggregateField(AggregateFunction::Count, alias: 'cnt')],
        );

        $result = $this->dataSource->aggregate($query);

        self::assertGreaterThanOrEqual(1, $result->getTotalCount());
    }

    public function test_fallback_on_is_all_operator(): void
    {
        // isAll has no ComparisonOperator equivalent — should fall back
        $filtered = $this->dataSource->filter_by(
            Filters::of(Filter::isAll('status', ['active', 'pending'])),
        );

        // Fallback returns empty IDs by default
        $ids = $filtered->get_data_ids(10, 0);
        self::assertIsArray($ids);
    }

    public function test_immutability(): void
    {
        $original = $this->dataSource;
        $filtered = $original->filter_by(
            Filters::of(Filter::is('status', 'active')),
        );

        // Original should be unchanged
        self::assertNotSame($original, $filtered);
        self::assertSame(4, $original->count());
    }
}

/**
 * Concrete implementation for testing.
 */
final class ConcreteQueryableDataSource extends QueryableDataSource
{
    public function id(): string
    {
        return 'test_queryable_source';
    }

    public function get_fields(): array
    {
        return [
            'name' => 'Name',
            'status' => 'Status',
            'amount' => 'Amount',
        ];
    }

    public function get_data_by_id(string $id): array
    {
        return ['id' => $id];
    }
}
