<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\Backend\WordPress\GravityForms\GravityFormsBackend;
use DataKit\DataViews\Query\Exception\QueryValidationException;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\SortDirection;
use DataKit\DataViews\Query\OrderBy;
use DataKit\DataViews\Query\Source;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * A field with no column mapping must not reach the SQL string.
 *
 * `buildColumnMap()` skips every key it cannot resolve to a real column, so the
 * map is the backend's answer to "which fields can I express in SQL". The
 * SELECT / GROUP BY / ORDER BY paths used to read it as
 * `$columnMap[$field] ?? $field` and interpolate the unresolved key directly
 * into the statement — as a bare SQL *expression*, not a quoted value.
 *
 * `SqlFilterCompiler::compileGroup()` performs the identical lookup on the same
 * map and drops the condition when it misses. One half of the class failed
 * closed while the other failed open, which is the tell: the fallback was never
 * a decision, it was a `??` filling in a value nobody chose.
 *
 * `QueryEngine::validateFields()` rejects fields absent from the schema before
 * compile, so this is not the only control. It is the control that survives a
 * caller holding a backend directly, and one that does not assume every name a
 * `describe()` implementation returns is a safe SQL identifier.
 */
final class UnmappedFieldFailsClosedTest extends TestCase
{
    private function source(): Source
    {
        return new Source('gravityforms', 'entries', ['form_id' => 1]);
    }

    /**
     * @param array<string, string> $columnMap
     */
    private function invoke(string $method, Query $query, array $columnMap = []): mixed
    {
        $backend = new GravityFormsBackend();
        $reflection = new ReflectionMethod($backend, $method);

        return $reflection->invoke($backend, $query, $columnMap);
    }

    public function test_an_unmapped_dimension_is_refused_rather_than_interpolated(): void
    {
        $query = new Query(
            type: QueryType::Aggregate,
            source: $this->source(),
            dimensions: [new SelectField('no_such_field')],
            metrics: [new AggregateField(AggregateFunction::Count)],
        );

        $this->expectException(QueryValidationException::class);
        $this->expectExceptionMessageMatches('/no_such_field/');

        $this->invoke('compileSelect', $query);
    }

    public function test_an_unmapped_metric_field_is_refused(): void
    {
        $query = new Query(
            type: QueryType::Aggregate,
            source: $this->source(),
            metrics: [new AggregateField(AggregateFunction::Sum, 'no_such_field')],
        );

        $this->expectException(QueryValidationException::class);
        $this->expectExceptionMessageMatches('/no_such_field/');

        $this->invoke('compileSelect', $query);
    }

    public function test_an_unmapped_group_by_is_refused(): void
    {
        $query = new Query(
            type: QueryType::Aggregate,
            source: $this->source(),
            dimensions: [new SelectField('no_such_field')],
            metrics: [new AggregateField(AggregateFunction::Count)],
        );

        $this->expectException(QueryValidationException::class);
        $this->expectExceptionMessageMatches('/no_such_field/');

        $this->invoke('compileGroupBy', $query);
    }

    public function test_an_unmapped_order_by_is_refused(): void
    {
        $query = new Query(
            type: QueryType::Browse,
            source: $this->source(),
            orderBy: [new OrderBy('no_such_field', SortDirection::Desc)],
        );

        $this->expectException(QueryValidationException::class);
        $this->expectExceptionMessageMatches('/no_such_field/');

        $this->invoke('compileOrderBy', $query);
    }

    /**
     * Positive control: the same call path with a mapped field must still
     * compile, or the four assertions above would pass for the wrong reason.
     */
    public function test_a_mapped_dimension_still_compiles(): void
    {
        $query = new Query(
            type: QueryType::Aggregate,
            source: $this->source(),
            dimensions: [new SelectField('status')],
            metrics: [new AggregateField(AggregateFunction::Count)],
        );

        $select = $this->invoke('compileSelect', $query, ['status' => 'e.status']);

        self::assertSame(['e.status AS `status`', 'COUNT(*) AS `count_*`'], $select);
    }

    /**
     * A backtick in an identifier must not close the quoting. Schema field
     * names come out of databases (meta keys, form field labels), so "no
     * identifier ever contains a backtick" is an assumption about data, not a
     * property of the code.
     */
    public function test_a_backtick_in_an_alias_cannot_break_out_of_the_quoting(): void
    {
        $query = new Query(
            type: QueryType::Aggregate,
            source: $this->source(),
            dimensions: [new SelectField('weird')],
            metrics: [new AggregateField(AggregateFunction::Count)],
        );

        $select = $this->invoke('compileSelect', $query, ['weird' => 'e.status']);

        self::assertSame('e.status AS `weird`', $select[0]);

        $query = new Query(
            type: QueryType::Aggregate,
            source: $this->source(),
            dimensions: [new SelectField('a`b')],
            metrics: [new AggregateField(AggregateFunction::Count)],
        );

        $select = $this->invoke('compileSelect', $query, ['a`b' => 'e.status']);

        self::assertSame('e.status AS `a``b`', $select[0]);
    }
}
