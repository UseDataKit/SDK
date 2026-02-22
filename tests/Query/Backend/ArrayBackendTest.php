<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\Backend\ArrayBackend;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Engine\BackendRegistry;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Engine\QueryEngine;
use DataKit\DataViews\Query\Limit;
use DataKit\DataViews\Query\LogicOperator;
use DataKit\DataViews\Query\OrderBy;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\SortDirection;
use DataKit\DataViews\Query\Source;
use DataKit\DataViews\Query\TimeBucket;
use DataKit\DataViews\Query\TimePreset;
use DataKit\DataViews\Query\TimeRange;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for ArrayBackend — the test oracle.
 */
final class ArrayBackendTest extends TestCase
{
    private array $fixtureData = [
        ['name' => 'Alice',   'amount' => 150,  'status' => 'active',   'category' => 'A', 'created_at' => '2024-01-15 10:00:00'],
        ['name' => 'Bob',     'amount' => 200,  'status' => 'active',   'category' => 'B', 'created_at' => '2024-02-20 14:30:00'],
        ['name' => 'Charlie', 'amount' => 0,    'status' => 'inactive', 'category' => 'A', 'created_at' => '2024-03-10 09:00:00'],
        ['name' => 'Diana',   'amount' => 75,   'status' => 'pending',  'category' => 'C', 'created_at' => '2024-04-05 16:45:00'],
        ['name' => 'Eve',     'amount' => 300,  'status' => 'active',   'category' => 'B', 'created_at' => '2024-05-25 11:15:00'],
    ];

    private array $fieldSchemas;

    protected function setUp(): void
    {
        $this->fieldSchemas = [
            new FieldSchema('name', 'Name', ColumnType::String),
            new FieldSchema('amount', 'Amount', ColumnType::Float, aggregatable: true),
            new FieldSchema('status', 'Status', ColumnType::String, enumValues: [
                'active' => 'Active', 'inactive' => 'Inactive', 'pending' => 'Pending',
            ]),
            new FieldSchema('category', 'Category', ColumnType::String),
            new FieldSchema('created_at', 'Created', ColumnType::Datetime, timezone: 'utc'),
        ];
    }

    private function makeBackend(?array $data = null): ArrayBackend
    {
        return new ArrayBackend('test', $data ?? $this->fixtureData, $this->fieldSchemas);
    }

    private function makeEngine(?ArrayBackend $backend = null): QueryEngine
    {
        $registry = new BackendRegistry();
        $registry->register($backend ?? $this->makeBackend());

        return new QueryEngine($registry, maxLimit: 5000);
    }

    private function browse(
        ?ConditionGroup $where = null,
        array $orderBy = [],
        ?Limit $limit = null,
        ?string $search = null,
        ?TimeRange $time = null,
    ): \DataKit\DataViews\Query\Engine\Result {
        $engine = $this->makeEngine();

        return $engine->execute(new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            time: $time,
            where: $where,
            orderBy: $orderBy,
            limit: $limit ?? new Limit(100),
            search: $search,
        ));
    }

    private function aggregate(
        array $dimensions = [],
        array $metrics = [],
        ?ConditionGroup $where = null,
        ?ConditionGroup $having = null,
        array $orderBy = [],
        ?Limit $limit = null,
        ?TimeRange $time = null,
    ): \DataKit\DataViews\Query\Engine\Result {
        $engine = $this->makeEngine();

        return $engine->execute(new Query(
            source: new Source('test'),
            type: QueryType::Aggregate,
            time: $time,
            dimensions: $dimensions,
            metrics: $metrics,
            where: $where,
            having: $having,
            orderBy: $orderBy,
            limit: $limit ?? new Limit(100),
        ));
    }

    // ===== FILTER TESTS =====

    public function test_filter_eq(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('status', ComparisonOperator::Eq, 'active')),
        );
        self::assertCount(3, $result);
    }

    public function test_filter_neq(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('status', ComparisonOperator::Neq, 'active')),
        );
        self::assertCount(2, $result);
    }

    public function test_filter_gt(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('amount', ComparisonOperator::Gt, 100)),
        );
        self::assertCount(3, $result); // 150, 200, 300
    }

    public function test_filter_gte(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('amount', ComparisonOperator::Gte, 150)),
        );
        self::assertCount(3, $result); // 150, 200, 300
    }

    public function test_filter_lt(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('amount', ComparisonOperator::Lt, 100)),
        );
        self::assertCount(2, $result); // 0, 75
    }

    public function test_filter_lte(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('amount', ComparisonOperator::Lte, 75)),
        );
        self::assertCount(2, $result); // 0, 75
    }

    public function test_filter_in(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('status', ComparisonOperator::In, ['active', 'pending'])),
        );
        self::assertCount(4, $result);
    }

    public function test_filter_not_in(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('status', ComparisonOperator::NotIn, ['active'])),
        );
        self::assertCount(2, $result);
    }

    public function test_filter_between(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('amount', ComparisonOperator::Between, [50, 200])),
        );
        self::assertCount(3, $result); // 150, 200, 75
    }

    public function test_filter_contains(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('name', ComparisonOperator::Contains, 'li')),
        );
        self::assertCount(2, $result); // Alice, Charlie
    }

    public function test_filter_not_contains(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('name', ComparisonOperator::NotContains, 'li')),
        );
        self::assertCount(3, $result);
    }

    public function test_filter_starts_with(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('name', ComparisonOperator::StartsWith, 'A')),
        );
        self::assertCount(1, $result);
    }

    public function test_filter_is_empty(): void
    {
        $dataWithEmpty = array_merge($this->fixtureData, [
            ['name' => '', 'amount' => 10, 'status' => 'active', 'category' => 'D', 'created_at' => '2024-06-01 00:00:00'],
        ]);

        $backend = new ArrayBackend('test', $dataWithEmpty, $this->fieldSchemas);
        $registry = new BackendRegistry();
        $registry->register($backend);
        $engine = new QueryEngine($registry, maxLimit: 5000);

        $result = $engine->execute(new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            where: ConditionGroup::and(new Condition('name', ComparisonOperator::IsEmpty, null)),
            limit: new Limit(100),
        ));

        self::assertCount(1, $result);
    }

    public function test_filter_is_not_empty(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('name', ComparisonOperator::IsNotEmpty, null)),
        );
        self::assertCount(5, $result);
    }

    // ===== LOGIC TESTS =====

    public function test_and_conditions(): void
    {
        $result = $this->browse(
            ConditionGroup::and(
                new Condition('status', ComparisonOperator::Eq, 'active'),
                new Condition('amount', ComparisonOperator::Gt, 100),
            ),
        );
        self::assertCount(3, $result); // Alice(150), Bob(200), Eve(300)
    }

    public function test_or_conditions(): void
    {
        $result = $this->browse(
            ConditionGroup::or(
                new Condition('status', ComparisonOperator::Eq, 'inactive'),
                new Condition('status', ComparisonOperator::Eq, 'pending'),
            ),
        );
        self::assertCount(2, $result);
    }

    public function test_nested_and_or(): void
    {
        $result = $this->browse(
            ConditionGroup::and(
                new Condition('category', ComparisonOperator::Eq, 'B'),
                ConditionGroup::or(
                    new Condition('amount', ComparisonOperator::Gt, 250),
                    new Condition('name', ComparisonOperator::Eq, 'Bob'),
                ),
            ),
        );
        self::assertCount(2, $result); // Bob and Eve
    }

    // ===== FALSY VALUE TESTS =====

    public function test_falsy_value_amount_zero(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('amount', ComparisonOperator::Eq, 0)),
        );
        self::assertCount(1, $result);
        self::assertSame('Charlie', $result->getRows()[0]['name']);
    }

    public function test_falsy_value_amount_zero_gte(): void
    {
        $result = $this->browse(
            ConditionGroup::and(new Condition('amount', ComparisonOperator::Gte, 0)),
        );
        self::assertCount(5, $result); // All rows have amount >= 0
    }

    // ===== SORT TESTS =====

    public function test_sort_asc(): void
    {
        $result = $this->browse(
            orderBy: [new OrderBy('amount', SortDirection::Asc)],
        );
        $amounts = array_column($result->getRows(), 'amount');
        self::assertSame([0, 75, 150, 200, 300], $amounts);
    }

    public function test_sort_desc(): void
    {
        $result = $this->browse(
            orderBy: [new OrderBy('amount', SortDirection::Desc)],
        );
        $amounts = array_column($result->getRows(), 'amount');
        self::assertSame([300, 200, 150, 75, 0], $amounts);
    }

    public function test_sort_multi_field(): void
    {
        $result = $this->browse(
            orderBy: [
                new OrderBy('status', SortDirection::Asc),
                new OrderBy('amount', SortDirection::Desc),
            ],
        );
        $names = array_column($result->getRows(), 'name');
        // active(300, 200, 150) -> inactive(0) -> pending(75)
        self::assertSame(['Eve', 'Bob', 'Alice', 'Charlie', 'Diana'], $names);
    }

    // ===== SEARCH TESTS =====

    public function test_search(): void
    {
        $result = $this->browse(search: 'alice');
        self::assertCount(1, $result);
        self::assertSame('Alice', $result->getRows()[0]['name']);
    }

    public function test_search_case_insensitive(): void
    {
        // "active" matches status field of 3 active rows + "inactive" also contains "active"
        $result = $this->browse(search: 'ACTIVE');
        self::assertCount(4, $result);
    }

    // ===== LIMIT/OFFSET TESTS =====

    public function test_limit(): void
    {
        $result = $this->browse(limit: new Limit(2));
        self::assertCount(2, $result);
        self::assertSame(5, $result->getTotalCount());
    }

    public function test_limit_offset(): void
    {
        $result = $this->browse(
            orderBy: [new OrderBy('amount', SortDirection::Asc)],
            limit: new Limit(2, 1),
        );
        $amounts = array_column($result->getRows(), 'amount');
        self::assertSame([75, 150], $amounts);
    }

    // ===== AGGREGATE TESTS =====

    public function test_aggregate_count(): void
    {
        $result = $this->aggregate(
            metrics: [new AggregateField(AggregateFunction::Count, alias: 'total')],
        );
        self::assertCount(1, $result);
        self::assertSame(5, $result->getRows()[0]['total']);
    }

    public function test_aggregate_count_distinct(): void
    {
        $result = $this->aggregate(
            metrics: [new AggregateField(AggregateFunction::CountDistinct, 'status', 'unique_statuses')],
        );
        self::assertSame(3, $result->getRows()[0]['unique_statuses']);
    }

    public function test_aggregate_sum(): void
    {
        $result = $this->aggregate(
            metrics: [new AggregateField(AggregateFunction::Sum, 'amount', 'total_amount')],
        );
        self::assertSame(725.0, $result->getRows()[0]['total_amount']);
    }

    public function test_aggregate_avg(): void
    {
        $result = $this->aggregate(
            metrics: [new AggregateField(AggregateFunction::Avg, 'amount', 'avg_amount')],
        );
        self::assertSame(145.0, $result->getRows()[0]['avg_amount']);
    }

    public function test_aggregate_min_max(): void
    {
        $result = $this->aggregate(
            metrics: [
                new AggregateField(AggregateFunction::Min, 'amount', 'min_amount'),
                new AggregateField(AggregateFunction::Max, 'amount', 'max_amount'),
            ],
        );
        self::assertSame(0.0, $result->getRows()[0]['min_amount']);
        self::assertSame(300.0, $result->getRows()[0]['max_amount']);
    }

    public function test_aggregate_group_by(): void
    {
        $result = $this->aggregate(
            dimensions: [new SelectField('status')],
            metrics: [new AggregateField(AggregateFunction::Count, alias: 'cnt')],
            orderBy: [new OrderBy('cnt', SortDirection::Desc)],
        );

        $rows = $result->getRows();
        self::assertCount(3, $rows);
        self::assertSame(3, $rows[0]['cnt']); // active
        self::assertSame('active', $rows[0]['status']);
    }

    public function test_aggregate_group_by_multi(): void
    {
        $result = $this->aggregate(
            dimensions: [new SelectField('status'), new SelectField('category')],
            metrics: [new AggregateField(AggregateFunction::Count, alias: 'cnt')],
        );
        // active-A(1), active-B(2), inactive-A(1), pending-C(1) = 4 groups
        self::assertCount(4, $result);
    }

    public function test_aggregate_with_where(): void
    {
        $result = $this->aggregate(
            dimensions: [new SelectField('status')],
            metrics: [new AggregateField(AggregateFunction::Sum, 'amount', 'total')],
            where: ConditionGroup::and(new Condition('category', ComparisonOperator::Eq, 'B')),
        );

        self::assertCount(1, $result); // Only active status in category B
        self::assertSame(500.0, $result->getRows()[0]['total']); // Bob(200) + Eve(300)
    }

    public function test_aggregate_having(): void
    {
        $result = $this->aggregate(
            dimensions: [new SelectField('status')],
            metrics: [new AggregateField(AggregateFunction::Count, alias: 'cnt')],
            having: ConditionGroup::and(new Condition('cnt', ComparisonOperator::Gt, 1)),
        );

        self::assertCount(1, $result); // Only "active" has count > 1
        self::assertSame('active', $result->getRows()[0]['status']);
    }

    // ===== TIME BUCKETING TESTS =====

    public function test_time_bucket_month(): void
    {
        $result = $this->aggregate(
            metrics: [new AggregateField(AggregateFunction::Count, alias: 'cnt')],
            time: new TimeRange('created_at', '2024-01-01 00:00:00', '2024-12-31 23:59:59', grain: TimeBucket::Month),
            orderBy: [new OrderBy('created_at_bucket', SortDirection::Asc)],
        );

        $buckets = array_column($result->getRows(), 'created_at_bucket');
        self::assertSame(['2024-01-01', '2024-02-01', '2024-03-01', '2024-04-01', '2024-05-01'], $buckets);
    }

    public function test_time_bucket_quarter(): void
    {
        $result = $this->aggregate(
            metrics: [new AggregateField(AggregateFunction::Count, alias: 'cnt')],
            time: new TimeRange('created_at', '2024-01-01 00:00:00', '2024-12-31 23:59:59', grain: TimeBucket::Quarter),
            orderBy: [new OrderBy('created_at_bucket', SortDirection::Asc)],
        );

        $buckets = array_column($result->getRows(), 'created_at_bucket');
        self::assertSame(['2024-Q1', '2024-Q2'], $buckets);
    }

    public function test_time_range_filter(): void
    {
        $result = $this->browse(
            time: new TimeRange('created_at', '2024-02-01 00:00:00', '2024-04-30 23:59:59'),
        );
        self::assertCount(3, $result); // Bob, Charlie, Diana
    }

    // ===== EMPTY DATA TESTS =====

    public function test_empty_data_browse(): void
    {
        $backend = new ArrayBackend('test', [], $this->fieldSchemas);
        $engine = $this->makeEngine($backend);

        $result = $engine->execute(new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            limit: new Limit(10),
        ));

        self::assertCount(0, $result);
        self::assertSame(0, $result->getTotalCount());
    }

    public function test_empty_data_aggregate(): void
    {
        $backend = new ArrayBackend('test', [], $this->fieldSchemas);
        $engine = $this->makeEngine($backend);

        $result = $engine->execute(new Query(
            source: new Source('test'),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, alias: 'cnt')],
            limit: new Limit(10),
        ));

        self::assertCount(1, $result);
        self::assertSame(0, $result->getRows()[0]['cnt']);
    }

    // ===== DESCRIBE TESTS =====

    public function test_describe(): void
    {
        $backend = $this->makeBackend();
        $schema = $backend->describe([]);

        self::assertSame('test', $schema->sourceType);
        self::assertCount(5, $schema->fields);
        self::assertTrue($schema->hasField('name'));
        self::assertSame('utc', $schema->getField('created_at')->timezone);
    }

    public function test_describe_auto_infers_fields(): void
    {
        $backend = new ArrayBackend('auto', $this->fixtureData);
        $schema = $backend->describe([]);

        self::assertCount(5, $schema->fields);
        self::assertTrue($schema->hasField('name'));
    }

    // ===== FULL PIPELINE (ENGINE) TESTS =====

    public function test_full_pipeline_browse(): void
    {
        $engine = $this->makeEngine();
        $result = $engine->execute(new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            where: ConditionGroup::and(
                new Condition('status', ComparisonOperator::Eq, 'active'),
            ),
            orderBy: [new OrderBy('amount', SortDirection::Desc)],
            limit: new Limit(2),
            search: 'e', // 'e' in all field values — status "active" has 'e', so all active match
        ));

        // active + search 'e': Alice(150), Bob(200), Eve(300) all match
        // sorted desc by amount: Eve(300), Bob(200), Alice(150) → limit 2
        self::assertCount(2, $result);
        self::assertSame('Eve', $result->getRows()[0]['name']);
        self::assertSame('Bob', $result->getRows()[1]['name']);
    }

    public function test_full_pipeline_aggregate(): void
    {
        $engine = $this->makeEngine();
        $result = $engine->execute(new Query(
            source: new Source('test'),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('category')],
            metrics: [
                new AggregateField(AggregateFunction::Sum, 'amount', 'total'),
                new AggregateField(AggregateFunction::Count, alias: 'cnt'),
            ],
            orderBy: [new OrderBy('total', SortDirection::Desc)],
            limit: new Limit(10),
        ));

        self::assertCount(3, $result); // A, B, C
        self::assertSame('B', $result->getRows()[0]['category']); // B: 200+300=500
        self::assertSame(500.0, $result->getRows()[0]['total']);
    }
}
