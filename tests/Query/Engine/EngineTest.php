<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Engine;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Engine\BackendRegistry;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\CompiledQuery;
use DataKit\DataViews\Query\Engine\CostEstimate;
use DataKit\DataViews\Query\Engine\DefaultResultHydrator;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Engine\QueryBackend;
use DataKit\DataViews\Query\Engine\QueryEngine;
use DataKit\DataViews\Query\Engine\Result;
use DataKit\DataViews\Query\Exception\CostThresholdExceededException;
use DataKit\DataViews\Query\Exception\FieldNotFoundException;
use DataKit\DataViews\Query\Exception\QueryExecutionException;
use DataKit\DataViews\Query\Exception\UnsupportedCapabilityException;
use DataKit\DataViews\Query\Limit;
use DataKit\DataViews\Query\OrderBy;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\Source;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Phase 2: QueryEngine, Result, BackendSchema, etc.
 */
final class EngineTest extends TestCase
{
    private function makeBackend(): QueryBackend
    {
        return new class implements QueryBackend {
            public static function isAvailable(): bool
            {
                return true;
            }

            public function sourceType(): string
            {
                return 'test';
            }

            public function capabilities(): array
            {
                return Capability::cases();
            }

            public function supports(Capability $cap): bool
            {
                return true;
            }

            public function describe(array $scope): BackendSchema
            {
                return new BackendSchema(
                    'test',
                    'Test Backend',
                    'A test backend for unit testing.',
                    Capability::cases(),
                    [
                        new FieldSchema('id', 'ID', ColumnType::Integer),
                        new FieldSchema('name', 'Name', ColumnType::String),
                        new FieldSchema('amount', 'Amount', ColumnType::Float, aggregatable: true),
                        new FieldSchema('status', 'Status', ColumnType::String, enumValues: ['active' => 'Active', 'inactive' => 'Inactive']),
                        new FieldSchema('created_at', 'Created', ColumnType::Datetime, timezone: 'utc'),
                    ],
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
                return new CompiledQuery(['query' => $query], 'test compilation');
            }

            public function execute(CompiledQuery $compiled): Result
            {
                return new Result(
                    ['id' => ColumnType::Integer, 'name' => ColumnType::String, 'amount' => ColumnType::Float],
                    [
                        ['id' => 1, 'name' => 'Alice', 'amount' => 100.5],
                        ['id' => 2, 'name' => 'Bob', 'amount' => 200.0],
                    ],
                    2,
                );
            }
        };
    }

    private function makeEngine(?QueryBackend $backend = null): QueryEngine
    {
        $registry = new BackendRegistry();

        if ($backend !== null) {
            $registry->register($backend);
        } else {
            $registry->register($this->makeBackend());
        }

        return new QueryEngine($registry, maxLimit: 1000);
    }

    public function test_execute_browse_query(): void
    {
        $engine = $this->makeEngine();
        $query = new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            limit: new Limit(10),
        );

        $result = $engine->execute($query);

        self::assertCount(2, $result);
        self::assertSame(2, $result->getTotalCount());
    }

    public function test_execute_aggregate_query(): void
    {
        $engine = $this->makeEngine();
        $query = new Query(
            source: new Source('test'),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('status')],
            metrics: [new AggregateField(AggregateFunction::Sum, 'amount', 'total')],
            limit: new Limit(100),
        );

        $result = $engine->execute($query);
        self::assertNotNull($result);
    }

    public function test_unknown_backend_throws(): void
    {
        $engine = $this->makeEngine();
        $query = new Query(
            source: new Source('nonexistent'),
            type: QueryType::Browse,
            limit: new Limit(10),
        );

        $this->expectException(QueryExecutionException::class);
        $this->expectExceptionMessage('No backend registered');
        $engine->execute($query);
    }

    public function test_unknown_field_throws_with_suggestion(): void
    {
        $engine = $this->makeEngine();
        $query = new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            where: ConditionGroup::and(
                new Condition('naem', ComparisonOperator::Eq, 'test'), // typo: "naem" -> "name"
            ),
            limit: new Limit(10),
        );

        try {
            $engine->execute($query);
            self::fail('Expected FieldNotFoundException');
        } catch (FieldNotFoundException $e) {
            self::assertSame('naem', $e->getContext()['field']);
            self::assertSame('name', $e->getContext()['suggestion']);
            self::assertContains('name', $e->getContext()['available_fields']);
        }
    }

    public function test_limit_clamped_when_exceeds_max(): void
    {
        $engine = $this->makeEngine();
        $query = new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            limit: new Limit(9999),
        );

        $result = $engine->execute($query);
        self::assertNotEmpty($result->getWarnings());
        self::assertStringContainsString('clamped', $result->getWarnings()[0]);
    }

    public function test_limit_clamped_when_missing(): void
    {
        $engine = $this->makeEngine();
        $query = new Query(
            source: new Source('test'),
            type: QueryType::Browse,
        );

        $result = $engine->execute($query);
        self::assertStringContainsString('No limit specified', $result->getWarnings()[0]);
    }

    public function test_cost_enforcement_blocks_expensive(): void
    {
        $expensiveBackend = new class implements QueryBackend {
            public static function isAvailable(): bool { return true; }
            public function sourceType(): string { return 'expensive'; }
            public function capabilities(): array { return Capability::cases(); }
            public function supports(Capability $cap): bool { return true; }
            public function describe(array $scope): BackendSchema
            {
                return new BackendSchema('expensive', 'Expensive', 'Costs a lot.', Capability::cases(), [
                    new FieldSchema('id', 'ID', ColumnType::Integer),
                ]);
            }
            public function schemaVersion(): int { return 1; }
            public function estimate(Query $query): ?CostEstimate
            {
                return new CostEstimate(0.95, true, ['entry_count' => 1000000], ['Add a time filter']);
            }
            public function compile(Query $query, BackendSchema $schema): CompiledQuery
            {
                return new CompiledQuery(null);
            }
            public function execute(CompiledQuery $compiled): Result
            {
                return new Result([], [], 0);
            }
        };

        $registry = new BackendRegistry();
        $registry->register($expensiveBackend);
        $engine = new QueryEngine($registry, enforceCost: true, costThreshold: 0.8);

        $query = new Query(
            source: new Source('expensive'),
            type: QueryType::Browse,
            limit: new Limit(10),
        );

        $this->expectException(CostThresholdExceededException::class);
        $engine->execute($query);
    }

    public function test_cost_enforcement_allows_null_estimate(): void
    {
        // Backend returns null estimate — query should proceed when enforceCost=true
        $engine = $this->makeEngine();
        $registry = new BackendRegistry();
        $registry->register($this->makeBackend());
        $engine = new QueryEngine($registry, enforceCost: true, costThreshold: 0.8, maxLimit: 1000);

        $query = new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            limit: new Limit(10),
        );

        $result = $engine->execute($query);
        self::assertNotNull($result);
    }

    public function test_unsupported_capability_throws(): void
    {
        $limitedBackend = new class implements QueryBackend {
            public static function isAvailable(): bool { return true; }
            public function sourceType(): string { return 'limited'; }
            public function capabilities(): array { return [Capability::FilterEq, Capability::LimitOffset]; }
            public function supports(Capability $cap): bool { return in_array($cap, $this->capabilities(), true); }
            public function describe(array $scope): BackendSchema
            {
                return new BackendSchema('limited', 'Limited', 'Limited caps.', $this->capabilities(), [
                    new FieldSchema('id', 'ID', ColumnType::Integer),
                    new FieldSchema('name', 'Name', ColumnType::String),
                ]);
            }
            public function schemaVersion(): int { return 1; }
            public function estimate(Query $query): ?CostEstimate { return null; }
            public function compile(Query $query, BackendSchema $schema): CompiledQuery
            {
                return new CompiledQuery(null);
            }
            public function execute(CompiledQuery $compiled): Result
            {
                return new Result([], [], 0);
            }
        };

        $registry = new BackendRegistry();
        $registry->register($limitedBackend);
        $engine = new QueryEngine($registry, maxLimit: 1000);

        $query = new Query(
            source: new Source('limited'),
            type: QueryType::Browse,
            where: ConditionGroup::and(
                new Condition('name', ComparisonOperator::Contains, 'test'),
            ),
            limit: new Limit(10),
        );

        $this->expectException(UnsupportedCapabilityException::class);
        $engine->execute($query);
    }

    public function test_describe(): void
    {
        $engine = $this->makeEngine();
        $schema = $engine->describe('test');

        self::assertSame('test', $schema->sourceType);
        self::assertCount(5, $schema->fields);
        self::assertTrue($schema->hasField('name'));
        self::assertFalse($schema->hasField('nonexistent'));
    }

    // --- BackendSchema tests ---

    public function test_backend_schema_find_closest_field(): void
    {
        $schema = new BackendSchema('test', 'Test', 'Test', [], [
            new FieldSchema('name', 'Name', ColumnType::String),
            new FieldSchema('status', 'Status', ColumnType::String),
            new FieldSchema('amount', 'Amount', ColumnType::Float),
        ]);

        self::assertSame('name', $schema->findClosestField('naem'));
        self::assertSame('status', $schema->findClosestField('staus'));
        self::assertNull($schema->findClosestField('completely_different'));
    }

    // --- Result tests ---

    public function test_result_countable_and_iterable(): void
    {
        $result = new Result(
            ['id' => ColumnType::Integer],
            [['id' => 1], ['id' => 2], ['id' => 3]],
            3,
        );

        self::assertCount(3, $result);
        self::assertSame(3, $result->getTotalCount());

        $ids = [];
        foreach ($result as $row) {
            $ids[] = $row['id'];
        }
        self::assertSame([1, 2, 3], $ids);
    }

    public function test_result_summarize(): void
    {
        $result = new Result(
            ['status' => ColumnType::String, 'amount' => ColumnType::Float],
            [
                ['status' => 'active', 'amount' => 100.0],
                ['status' => 'active', 'amount' => 200.0],
                ['status' => 'inactive', 'amount' => 50.0],
            ],
            3,
        );

        $summary = $result->summarize(2);

        self::assertSame(3, $summary['total']);
        self::assertSame(3, $summary['returned']);
        self::assertCount(2, $summary['preview']);
        self::assertArrayHasKey('distributions', $summary);
        self::assertSame(['active' => 2, 'inactive' => 1], $summary['distributions']['status']);
    }

    public function test_result_serialization_roundtrip(): void
    {
        $result = new Result(
            ['id' => ColumnType::Integer, 'name' => ColumnType::String],
            [['id' => 1, 'name' => 'test']],
            1,
            ['a warning'],
            false,
            10,
            0,
        );

        $restored = Result::fromArray($result->toArray());
        self::assertSame($result->getTotalCount(), $restored->getTotalCount());
        self::assertCount(1, $restored);
        self::assertSame(['a warning'], $restored->getWarnings());
    }

    // --- DefaultResultHydrator tests ---

    public function test_hydrator_casts_types(): void
    {
        $hydrator = new DefaultResultHydrator();
        $schema = [
            'id' => ColumnType::Integer,
            'amount' => ColumnType::Float,
            'active' => ColumnType::Boolean,
            'name' => ColumnType::String,
        ];
        $rows = [
            ['id' => '42', 'amount' => '99.5', 'active' => 1, 'name' => 123],
        ];

        $result = $hydrator->hydrate($schema, $rows);

        self::assertSame(42, $result['rows'][0]['id']);
        self::assertSame(99.5, $result['rows'][0]['amount']);
        self::assertTrue($result['rows'][0]['active']);
        self::assertSame('123', $result['rows'][0]['name']);
        self::assertEmpty($result['warnings']);
    }

    public function test_hydrator_strict_mode_warns_on_invalid_cast(): void
    {
        $hydrator = new DefaultResultHydrator(strict: true);
        $schema = ['count' => ColumnType::Integer];
        $rows = [['count' => 'not_a_number']];

        $result = $hydrator->hydrate($schema, $rows);

        self::assertCount(1, $result['warnings']);
        self::assertStringContainsString('cannot cast', $result['warnings'][0]);
        self::assertSame('not_a_number', $result['rows'][0]['count']); // Kept as string
    }

    // --- FieldSchema tests ---

    public function test_field_schema_serialization(): void
    {
        $field = new FieldSchema(
            'status',
            'Status',
            ColumnType::String,
            [ComparisonOperator::Eq, ComparisonOperator::In],
            ['active' => 'Active', 'inactive' => 'Inactive'],
            'The entry status',
            true,
            true,
            false,
            'utc',
        );

        $array = $field->toArray();
        self::assertSame('status', $array['key']);
        self::assertSame(['eq', 'in'], $array['operators']);
        self::assertSame('utc', $array['timezone']);

        $restored = FieldSchema::fromArray($array);
        self::assertSame($field->key, $restored->key);
        self::assertSame($field->type, $restored->type);
        self::assertCount(2, $restored->operators);
    }

    // --- Capability mapping tests ---

    public function test_capability_from_comparison_operator(): void
    {
        self::assertSame(Capability::FilterEq, Capability::fromComparisonOperator(ComparisonOperator::Eq));
        self::assertSame(Capability::FilterContains, Capability::fromComparisonOperator(ComparisonOperator::Contains));
        self::assertSame(Capability::FilterBetween, Capability::fromComparisonOperator(ComparisonOperator::Between));
    }

    public function test_capability_from_aggregate_function(): void
    {
        self::assertSame(Capability::AggCount, Capability::fromAggregateFunction(AggregateFunction::Count));
        self::assertSame(Capability::AggSum, Capability::fromAggregateFunction(AggregateFunction::Sum));
        self::assertSame(Capability::AggCountDistinct, Capability::fromAggregateFunction(AggregateFunction::CountDistinct));
    }

    // --- BackendRegistry tests ---

    public function test_registry_operations(): void
    {
        $registry = new BackendRegistry();
        self::assertFalse($registry->has('test'));
        self::assertNull($registry->get('test'));
        self::assertSame([], $registry->sourceTypes());

        $registry->register($this->makeBackend());
        self::assertTrue($registry->has('test'));
        self::assertNotNull($registry->get('test'));
        self::assertSame(['test'], $registry->sourceTypes());
    }
}
