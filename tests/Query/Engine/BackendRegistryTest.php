<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Engine;

use DataKit\DataViews\Query\Engine\BackendRegistry;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\CompiledQuery;
use DataKit\DataViews\Query\Engine\CostEstimate;
use DataKit\DataViews\Query\Engine\QueryBackend;
use DataKit\DataViews\Query\Engine\Result;
use DataKit\DataViews\Query\Exception\BackendCollisionException;
use DataKit\DataViews\Query\Query;
use PHPUnit\Framework\TestCase;

/**
 * Registration must refuse to silently displace a backend.
 *
 * Two backends claiming one source type is either a mistake or a deliberate
 * substitution. Only the deliberate one is allowed, and it has to say so.
 */
final class BackendRegistryTest extends TestCase
{
    public function test_distinct_source_types_coexist(): void
    {
        $registry = new BackendRegistry();
        $registry->register($this->backend('gravity_forms'));
        $registry->register($this->backend('edd'));

        self::assertSame(['gravity_forms', 'edd'], $registry->sourceTypes());
    }

    public function test_a_contested_source_type_throws(): void
    {
        $registry = new BackendRegistry();
        $registry->register($this->backend('gravity_forms'));

        $this->expectException(BackendCollisionException::class);

        $registry->register($this->backend('gravity_forms'));
    }

    public function test_the_collision_names_the_source_type_and_both_backends(): void
    {
        $registry = new BackendRegistry();
        $incumbent = $this->backend('gravity_forms');
        $challenger = $this->backend('gravity_forms');

        $registry->register($incumbent);

        try {
            $registry->register($challenger);
            self::fail('Expected a BackendCollisionException.');
        } catch (BackendCollisionException $e) {
            $context = $e->getContext();

            self::assertSame('gravity_forms', $context['source_type']);
            self::assertSame($incumbent::class, $context['registered']);
            self::assertSame($challenger::class, $context['incoming']);
            self::assertStringContainsString('gravity_forms', $e->getMessage());
        }
    }

    public function test_a_refused_registration_leaves_the_incumbent_in_place(): void
    {
        $registry = new BackendRegistry();
        $incumbent = $this->backend('gravity_forms');
        $registry->register($incumbent);

        try {
            $registry->register($this->backend('gravity_forms'));
        } catch (BackendCollisionException) {
            // Expected.
        }

        self::assertSame($incumbent, $registry->get('gravity_forms'));
    }

    public function test_replace_flag_substitutes_deliberately(): void
    {
        $registry = new BackendRegistry();
        $registry->register($this->backend('gravity_forms'));

        $replacement = $this->backend('gravity_forms');
        $registry->register($replacement, replace: true);

        self::assertSame($replacement, $registry->get('gravity_forms'));
    }

    public function test_replace_method_substitutes_deliberately(): void
    {
        $registry = new BackendRegistry();
        $registry->register($this->backend('gravity_forms'));

        $replacement = $this->backend('gravity_forms');
        $registry->replace($replacement);

        self::assertSame($replacement, $registry->get('gravity_forms'));
    }

    public function test_replace_registers_an_uncontested_source_type(): void
    {
        $registry = new BackendRegistry();
        $backend = $this->backend('gravity_forms');

        $registry->replace($backend);

        self::assertSame($backend, $registry->get('gravity_forms'));
    }

    public function test_registering_the_same_instance_twice_is_a_no_op(): void
    {
        $registry = new BackendRegistry();
        $backend = $this->backend('gravity_forms');

        $registry->register($backend);
        $registry->register($backend);

        self::assertSame(['gravity_forms'], $registry->sourceTypes());
    }

    private function backend(string $sourceType): QueryBackend
    {
        return new class ($sourceType) implements QueryBackend {
            public function __construct(private string $sourceType)
            {
            }

            public function sourceType(): string
            {
                return $this->sourceType;
            }

            public function capabilities(): array
            {
                return [];
            }

            public function supports(Capability $cap): bool
            {
                return false;
            }

            public function describe(array $scope): BackendSchema
            {
                return new BackendSchema($this->sourceType, '', '', [], []);
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
                throw new \LogicException('Not needed for registration tests.');
            }

            public function execute(CompiledQuery $compiled): Result
            {
                throw new \LogicException('Not needed for registration tests.');
            }

            public static function isAvailable(): bool
            {
                return true;
            }
        };
    }
}
