<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Adapter;

use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\Query\Adapter\LegacyDataSourceAdapter;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Engine\BackendRegistry;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\QueryEngine;
use DataKit\DataViews\Query\Limit;
use DataKit\DataViews\Query\LogicOperator;
use DataKit\DataViews\Query\OrderBy;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SortDirection;
use DataKit\DataViews\Query\Source;
use PHPUnit\Framework\TestCase;

/**
 * Tests for LegacyDataSourceAdapter.
 */
final class LegacyDataSourceAdapterTest extends TestCase
{
    private LegacyDataSourceAdapter $adapter;
    private QueryEngine $engine;

    protected function setUp(): void
    {
        $dataSource = new ArrayDataSource('test_source', [
            '1' => ['name' => 'Alice', 'status' => 'active', 'email' => 'alice@example.com'],
            '2' => ['name' => 'Bob', 'status' => 'inactive', 'email' => 'bob@example.com'],
            '3' => ['name' => 'Charlie', 'status' => 'active', 'email' => 'charlie@example.com'],
            '4' => ['name' => 'Diana', 'status' => 'pending', 'email' => 'diana@example.com'],
        ]);

        $this->adapter = new LegacyDataSourceAdapter($dataSource);

        $registry = new BackendRegistry();
        $registry->register($this->adapter);
        $this->engine = new QueryEngine($registry);
    }

    public function test_source_type(): void
    {
        self::assertSame('legacy_test_source', $this->adapter->sourceType());
    }

    public function test_default_capabilities(): void
    {
        $caps = $this->adapter->capabilities();
        self::assertContains(Capability::FilterEq, $caps);
        self::assertContains(Capability::FilterIn, $caps);
        self::assertContains(Capability::Search, $caps);
        self::assertContains(Capability::OrderBy, $caps);
        self::assertContains(Capability::LimitOffset, $caps);
    }

    public function test_supports(): void
    {
        self::assertTrue($this->adapter->supports(Capability::FilterEq));
        self::assertFalse($this->adapter->supports(Capability::TimeBucket));
    }

    public function test_describe(): void
    {
        $schema = $this->adapter->describe([]);
        self::assertSame('legacy_test_source', $schema->sourceType);
        self::assertTrue($schema->hasField('name'));
        self::assertTrue($schema->hasField('status'));
        self::assertTrue($schema->hasField('email'));
    }

    public function test_schema_version(): void
    {
        self::assertSame(1, $this->adapter->schemaVersion());
    }

    public function test_estimate_returns_null(): void
    {
        $query = new Query(
            source: new Source('legacy_test_source'),
            type: QueryType::Browse,
            limit: new Limit(10),
        );
        self::assertNull($this->adapter->estimate($query));
    }

    public function test_browse_no_filters(): void
    {
        $result = $this->engine->execute(new Query(
            source: new Source('legacy_test_source'),
            type: QueryType::Browse,
            limit: new Limit(10),
        ));

        self::assertSame(4, $result->getTotalCount());
        self::assertCount(4, $result);
    }

    public function test_browse_with_eq_filter(): void
    {
        $result = $this->engine->execute(new Query(
            source: new Source('legacy_test_source'),
            type: QueryType::Browse,
            where: ConditionGroup::and(
                new Condition('status', ComparisonOperator::Eq, 'active'),
            ),
            limit: new Limit(10),
        ));

        self::assertSame(2, $result->getTotalCount());
        self::assertCount(2, $result);
    }

    public function test_browse_with_in_filter(): void
    {
        $result = $this->engine->execute(new Query(
            source: new Source('legacy_test_source'),
            type: QueryType::Browse,
            where: ConditionGroup::and(
                new Condition('status', ComparisonOperator::In, ['active', 'pending']),
            ),
            limit: new Limit(10),
        ));

        self::assertSame(3, $result->getTotalCount());
    }

    public function test_browse_with_search(): void
    {
        $result = $this->engine->execute(new Query(
            source: new Source('legacy_test_source'),
            type: QueryType::Browse,
            limit: new Limit(10),
            search: 'alice',
        ));

        // Search matches across all field values
        self::assertGreaterThanOrEqual(1, $result->getTotalCount());
    }

    public function test_browse_with_sort(): void
    {
        $result = $this->engine->execute(new Query(
            source: new Source('legacy_test_source'),
            type: QueryType::Browse,
            orderBy: [new OrderBy('name', SortDirection::Desc)],
            limit: new Limit(10),
        ));

        $rows = $result->getRows();
        self::assertCount(4, $rows);
        // DESC sort by name: Diana > Charlie > Bob > Alice
        self::assertSame('Diana', $rows[0]['name']);
        self::assertSame('Alice', $rows[3]['name']);
    }

    public function test_browse_with_limit_offset(): void
    {
        $result = $this->engine->execute(new Query(
            source: new Source('legacy_test_source'),
            type: QueryType::Browse,
            limit: new Limit(2, 1),
        ));

        self::assertSame(4, $result->getTotalCount());
        self::assertCount(2, $result);
    }

    public function test_neq_filter(): void
    {
        $result = $this->engine->execute(new Query(
            source: new Source('legacy_test_source'),
            type: QueryType::Browse,
            where: ConditionGroup::and(
                new Condition('status', ComparisonOperator::Neq, 'active'),
            ),
            limit: new Limit(10),
        ));

        self::assertSame(2, $result->getTotalCount());
    }

    public function test_not_in_not_in_default_capabilities(): void
    {
        // NotIn maps to isNone which uses array_intersect — doesn't work for scalar fields.
        // Verify it's excluded from default capabilities.
        self::assertNotContains(Capability::FilterNotIn, $this->adapter->capabilities());
    }

    public function test_field_schemas_default_to_string_type(): void
    {
        $schema = $this->adapter->describe([]);

        foreach ($schema->fields as $field) {
            self::assertSame(ColumnType::String, $field->type);
        }
    }

    public function test_bridgeable_operators_only(): void
    {
        $schema = $this->adapter->describe([]);
        $field = $schema->getField('name');

        // All operators should be bridgeable (have a toOperator() that returns non-null)
        foreach ($field->operators as $op) {
            self::assertNotNull($op->toOperator(), "Operator {$op->value} should be bridgeable");
        }
    }
}
