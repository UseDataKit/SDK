<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\Backend\WordPress\GravityForms\GravityFormsBackend;
use DataKit\DataViews\Query\Backend\WordPress\SqlFilterCompiler;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Exception\QueryValidationException;
use DataKit\DataViews\Query\LogicOperator;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * A filter that cannot be compiled must not simply disappear.
 *
 * `compileGroup()` skipped a condition whose field was absent from the column
 * map. Dropping a filter widens the result set, so the failure mode is a query
 * that runs, returns rows, and returns more of them than it was asked for —
 * an `owner_email = $victim` restriction vanishing leaves an unfiltered scan
 * that reads as a successful query.
 *
 * `CompiledQuery` has no warnings channel, so there was nowhere for the
 * omission to surface even if a caller wanted to check. Refusing is the only
 * outcome that cannot be mistaken for success.
 */
final class DroppedFilterTest extends TestCase
{
    /**
     * @param array<string, string> $columnMap
     * @return array{clause: string, params: array}
     */
    private function compile(ConditionGroup $group, array $columnMap): array
    {
        $compiler = new SqlFilterCompiler();

        return (new ReflectionMethod($compiler, 'compileGroup'))->invoke($compiler, $group, $columnMap);
    }

    public function test_a_condition_on_an_unmapped_field_is_refused(): void
    {
        $group = new ConditionGroup(LogicOperator::And, [
            new Condition('owner_email', ComparisonOperator::Eq, 'victim@example.com'),
        ]);

        $this->expectException(QueryValidationException::class);
        $this->expectExceptionMessageMatches('/owner_email/');

        $this->compile($group, ['status' => 'e.status']);
    }

    /**
     * The dangerous shape: one compilable condition beside one that is not.
     * Dropping the second leaves a narrower-looking query that is in fact
     * broader than requested.
     */
    public function test_a_partly_compilable_group_is_refused_whole(): void
    {
        $group = new ConditionGroup(LogicOperator::And, [
            new Condition('status', ComparisonOperator::Eq, 'active'),
            new Condition('owner_email', ComparisonOperator::Eq, 'victim@example.com'),
        ]);

        $this->expectException(QueryValidationException::class);

        $this->compile($group, ['status' => 'e.status']);
    }

    /**
     * Positive control.
     */
    public function test_a_fully_mapped_group_still_compiles(): void
    {
        $group = new ConditionGroup(LogicOperator::And, [
            new Condition('status', ComparisonOperator::Eq, 'active'),
        ]);

        self::assertSame('(e.status = %s)', $this->compile($group, ['status' => 'e.status'])['clause']);
    }

    /**
     * HAVING resolves against output aliases rather than source columns, and
     * the map was built from metrics alone. A HAVING on a dimension alias was
     * therefore dropped — invisible while filters failed silently, and a hard
     * error the moment they stopped. The map has to carry both.
     */
    public function test_having_can_reference_a_dimension_alias(): void
    {
        $query = new Query(
            type: QueryType::Aggregate,
            source: new \DataKit\DataViews\Query\Source('gravityforms', 'entries', ['form_id' => 1]),
            dimensions: [new SelectField('status', 'entry_status')],
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
            having: new ConditionGroup(LogicOperator::And, [
                new Condition('entry_status', ComparisonOperator::Eq, 'active'),
            ]),
        );

        $backend = new GravityFormsBackend();
        $method = new ReflectionMethod($backend, 'compileHaving');

        [$clauses, $params] = $method->invoke($backend, $query, ['status' => 'e.status']);

        self::assertSame(['(e.status = %s)'], $clauses);
        self::assertSame(['active'], $params);
    }

    public function test_having_on_a_metric_alias_still_works(): void
    {
        $query = new Query(
            type: QueryType::Aggregate,
            source: new \DataKit\DataViews\Query\Source('gravityforms', 'entries', ['form_id' => 1]),
            dimensions: [new SelectField('status')],
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
            having: new ConditionGroup(LogicOperator::And, [
                new Condition('total', ComparisonOperator::Gt, 5),
            ]),
        );

        $backend = new GravityFormsBackend();
        $method = new ReflectionMethod($backend, 'compileHaving');

        [$clauses] = $method->invoke($backend, $query, ['status' => 'e.status']);

        self::assertSame(['(COUNT(*) > %s)'], $clauses);
    }
}
