<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\Exception\QueryValidationException;
use DataKit\DataViews\Query\SelectField;
use PHPUnit\Framework\TestCase;

/**
 * An output alias is an SQL identifier, and identifiers cannot be bound.
 *
 * Backends emit ``expr AS `{$alias}` `` and reference the alias again in
 * ORDER BY and HAVING. `wpdb::prepare()` binds values, never identifiers, so
 * an alias carrying a backtick left its quotes and the rest of the string was
 * SQL. Proven before the fix: an alias of
 *
 *   x` , (SELECT 1) AS `pwn
 *
 * compiled to ``col AS `x` , (SELECT 1) AS `pwn` `` — identifier closed,
 * subquery injected, identifier reopened. Every wpdb backend shared the sink,
 * so the guard belongs at the value object, before SQL is ever built.
 */
final class OutputAliasTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function hostileAliases(): array
    {
        return [
            'backtick escape'    => ['x` , (SELECT 1) AS `pwn'],
            'bare backtick'      => ['`'],
            'statement break'    => ['a; DROP TABLE wp_posts'],
            'comment'            => ['a-- '],
            'block comment'      => ['a/*x*/'],
            'quote'              => ["a'b"],
            'double quote'       => ['a"b'],
            'paren'              => ['count(*)'],
            'space'              => ['two words'],
            'newline'            => ["a\nb"],
            'null byte'          => ["a\0b"],
        ];
    }

    /**
     * @dataProvider hostileAliases
     */
    public function testDimensionRejectsAnAliasThatCanLeaveItsQuotes(string $alias): void
    {
        $this->expectException(QueryValidationException::class);

        new SelectField(field: 'status', alias: $alias);
    }

    /**
     * @dataProvider hostileAliases
     */
    public function testMetricRejectsAnAliasThatCanLeaveItsQuotes(string $alias): void
    {
        $this->expectException(QueryValidationException::class);

        new AggregateField(function: AggregateFunction::Count, field: null, alias: $alias);
    }

    /**
     * Positive control. A rule that rejected everything would satisfy the
     * tests above while breaking every real caller, so the shapes this
     * library actually generates must survive.
     *
     * @return array<string, array{0: string}>
     */
    public static function legitimateAliases(): array
    {
        return [
            'plain'            => ['status'],
            'snake case'       => ['m_count'],
            'sum prefix'       => ['sum_revenue'],
            'time bucket'      => ['created_at_bucket'],
            'gravity forms'    => ['field:5.1'],
            'namespaced field' => ['form:42.field:1'],
            'hyphenated'       => ['order-total'],
            'digits'           => ['q1'],
        ];
    }

    /**
     * @dataProvider legitimateAliases
     */
    public function testTheAliasesThisLibraryGeneratesAreAccepted(string $alias): void
    {
        self::assertSame($alias, (new SelectField(field: 'x', alias: $alias))->outputName());
        self::assertSame(
            $alias,
            (new AggregateField(function: AggregateFunction::Count, field: null, alias: $alias))->outputName()
        );
    }

    public function testAnAbsentAliasIsStillAllowed(): void
    {
        self::assertSame('status', (new SelectField(field: 'status'))->outputName());
    }

    public function testAnEmptyAliasIsRejected(): void
    {
        $this->expectException(QueryValidationException::class);

        new SelectField(field: 'status', alias: '');
    }
}
