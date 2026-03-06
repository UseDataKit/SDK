<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Engine;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\Engine\BackendRegistry;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\CompiledQuery;
use DataKit\DataViews\Query\Engine\CostEstimate;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Engine\QueryBackend;
use DataKit\DataViews\Query\Engine\QueryEngine;
use DataKit\DataViews\Query\Engine\Result;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\Source;
use PHPUnit\Framework\TestCase;

/**
 * Tests that QueryEngine cache keys include source type information
 * so that tag-based invalidation (deleteByTag) actually works.
 */
final class QueryEngineCacheKeyTest extends TestCase
{
    private InMemoryCacheProvider $cache;
    private QueryEngine $engine;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCacheProvider();

        $registry = new BackendRegistry();
        $registry->register($this->createStubBackend('woocommerce'));
        $registry->register($this->createStubBackend('edd'));
        $registry->register($this->createStubBackend('gravity_forms'));
        $registry->register($this->createStubBackend('wordpress_users'));

        $this->engine = new QueryEngine(
            backends: $registry,
            cache: $this->cache,
            cacheTtl: 300,
        );
    }

    public function test_cache_key_contains_source_type(): void
    {
        $query = new Query(
            source: new Source('woocommerce', 'orders'),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Sum, 'total', 'sum_total')],
        );

        $this->engine->execute($query);

        $keys = $this->cache->keys();
        self::assertCount(1, $keys, 'Expected exactly one cached entry.');

        $key = $keys[0];
        self::assertStringContainsString(
            'woocommerce',
            $key,
            'Cache key must contain the source type so deleteByTag can match it.',
        );
    }

    public function test_cache_key_contains_form_id_for_gravity_forms(): void
    {
        $query = new Query(
            source: Source::gravityFormsEntry(42),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, 'id', 'count')],
        );

        $this->engine->execute($query);

        $keys = $this->cache->keys();
        self::assertCount(1, $keys);

        $key = $keys[0];
        self::assertStringContainsString(
            'form_42',
            $key,
            'Cache key must contain form_id so deleteByTag("form_42") can invalidate it.',
        );
    }

    public function test_delete_by_tag_removes_only_matching_source_entries(): void
    {
        // Execute queries for two different source types.
        $wooQuery = new Query(
            source: new Source('woocommerce', 'orders'),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Sum, 'total', 'sum_total')],
        );

        $eddQuery = new Query(
            source: new Source('edd', 'orders'),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Sum, 'total', 'sum_total')],
        );

        $this->engine->execute($wooQuery);
        $this->engine->execute($eddQuery);

        self::assertSame(2, $this->cache->count(), 'Two entries should be cached.');

        // Invalidate only WooCommerce.
        $this->cache->deleteByTag('woocommerce');

        self::assertSame(1, $this->cache->count(), 'Only the EDD entry should remain.');

        // The remaining key should be for EDD, not WooCommerce.
        $remainingKey = $this->cache->keys()[0];
        self::assertStringContainsString('edd', $remainingKey);
        self::assertStringNotContainsString('woocommerce', $remainingKey);
    }

    public function test_delete_by_tag_removes_form_specific_entries(): void
    {
        // Cache results for two different forms.
        $form1Query = new Query(
            source: Source::gravityFormsEntry(1),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, 'id', 'count')],
        );

        $form2Query = new Query(
            source: Source::gravityFormsEntry(2),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, 'id', 'count')],
        );

        $this->engine->execute($form1Query);
        $this->engine->execute($form2Query);

        self::assertSame(2, $this->cache->count());

        // Invalidate only form 1.
        $this->cache->deleteByTag('form_1');

        self::assertSame(1, $this->cache->count(), 'Only form 2 entry should remain.');

        $remainingKey = $this->cache->keys()[0];
        self::assertStringContainsString('form_2', $remainingKey);
    }

    public function test_different_source_types_produce_different_cache_keys(): void
    {
        // Same query shape, different source types.
        $wooQuery = new Query(
            source: new Source('woocommerce', 'orders'),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Sum, 'total', 'sum_total')],
        );

        $eddQuery = new Query(
            source: new Source('edd', 'orders'),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Sum, 'total', 'sum_total')],
        );

        $this->engine->execute($wooQuery);
        $this->engine->execute($eddQuery);

        $keys = $this->cache->keys();
        self::assertCount(2, $keys, 'Two separate cache entries should exist.');
        self::assertNotEquals($keys[0], $keys[1], 'Different sources must produce different keys.');
    }

    /**
     * Create a stub backend that returns empty results.
     */
    private function createStubBackend(string $sourceType): QueryBackend
    {
        return new class($sourceType) implements QueryBackend {
            public function __construct(private readonly string $type)
            {
            }

            public function sourceType(): string
            {
                return $this->type;
            }

            public function capabilities(): array
            {
                return [Capability::AggCount, Capability::AggSum];
            }

            public function supports(Capability $cap): bool
            {
                return in_array($cap, [Capability::AggCount, Capability::AggSum], true);
            }

            public function describe(array $scope): BackendSchema
            {
                return new BackendSchema(
                    $this->type,
                    $this->type,
                    '',
                    [Capability::AggCount, Capability::AggSum],
                    [
                        new FieldSchema('id', 'ID', ColumnType::Integer, aggregatable: true),
                        new FieldSchema('total', 'Total', ColumnType::Float, aggregatable: true),
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
                return new CompiledQuery('SELECT 1');
            }

            public function execute(CompiledQuery $compiled): Result
            {
                return new Result(
                    ['sum_total' => ColumnType::Float],
                    [['sum_total' => 100.0]],
                    1,
                );
            }

            public static function isAvailable(): bool
            {
                return true;
            }
        };
    }
}
