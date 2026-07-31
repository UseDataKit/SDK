<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress;

use DataKit\DataViews\Query\Backend\WordPress\SqlFilterCompiler;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Exception\QueryValidationException;
use DataKit\DataViews\Query\LogicOperator;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * A compiled filter group must be safe to AND onto a scope predicate.
 *
 * Backends build `WHERE {scope} AND {filters}`. SQL binds AND tighter than OR,
 * so an unparenthesised top-level OR group attaches the scope to its LEFT
 * disjunct only:
 *
 *   WHERE form_id IN (1) AND status IN ('active') AND id > 0 OR form_id > 0
 *   ==   (form_id IN (1) AND status IN ('active') AND id > 0) OR (form_id > 0)
 *
 * The right side is unscoped: every row of the table, regardless of form,
 * status, or visibility. It needs no hostile input — every field is
 * schema-valid — which is why nothing upstream could catch it.
 *
 * `ArrayBackend` evaluates the same Query with correct precedence, so before
 * this fix two backends disagreed about what one Query meant. That divergence
 * is the proof it was a bug rather than a convention.
 */
final class SqlFilterCompilerPrecedenceTest extends TestCase
{
    /**
     * @param array<string, string> $columnMap
     * @return array{clause: string, params: array}
     */
    private function compile(ConditionGroup $group, array $columnMap): array
    {
        $compiler = new SqlFilterCompiler();
        $method = new ReflectionMethod($compiler, 'compileGroup');

        return $method->invoke($compiler, $group, $columnMap);
    }

    /**
     * @return array<string, string>
     */
    private function columnMap(): array
    {
        return ['id' => 'e.id', 'form_id' => 'e.form_id', 'status' => 'e.status'];
    }

    public function testATopLevelOrGroupIsParenthesised(): void
    {
        $group = new ConditionGroup(LogicOperator::Or, [
            new Condition('id', ComparisonOperator::Gt, 0),
            new Condition('form_id', ComparisonOperator::Gt, 0),
        ]);

        $clause = $this->compile($group, $this->columnMap())['clause'];

        self::assertSame('(e.id > %s OR e.form_id > %s)', $clause);
    }

    /**
     * The actual defect, stated as the invariant rather than as a string:
     * ANDing a scope onto the fragment must not leave a disjunct outside it.
     */
    public function testAScopeCannotEscapeThroughAnOrFilter(): void
    {
        $group = new ConditionGroup(LogicOperator::Or, [
            new Condition('id', ComparisonOperator::Gt, 0),
            new Condition('form_id', ComparisonOperator::Gt, 0),
        ]);

        $clause = $this->compile($group, $this->columnMap())['clause'];
        $where = "e.form_id IN (%d) AND " . $clause;

        // With the fragment parenthesised, no top-level OR exists in the
        // assembled WHERE, so the scope binds to the whole filter.
        self::assertSame(
            0,
            $this->topLevelOrCount($where),
            'A scope ANDed onto this fragment would apply to only part of it: ' . $where
        );
    }

    public function testAndGroupsAreParenthesisedToo(): void
    {
        $group = new ConditionGroup(LogicOperator::And, [
            new Condition('id', ComparisonOperator::Gt, 0),
            new Condition('status', ComparisonOperator::Eq, 'active'),
        ]);

        self::assertSame(
            '(e.id > %s AND e.status = %s)',
            $this->compile($group, $this->columnMap())['clause'],
            'An AND group is safe unparenthesised today, but relying on that '
            . 'makes correctness depend on which operator a caller happened to '
            . 'choose.'
        );
    }

    public function testASingleConditionIsStillParenthesised(): void
    {
        $group = new ConditionGroup(LogicOperator::Or, [
            new Condition('id', ComparisonOperator::Gt, 0),
        ]);

        self::assertSame('(e.id > %s)', $this->compile($group, $this->columnMap())['clause']);
    }

    /**
     * The compiler emits "" rather than "()" for a group with no clauses.
     * One of the two ways to reach that state is closed at construction.
     */
    public function testAConditionGroupCannotBeConstructedEmpty(): void
    {
        $this->expectException(QueryValidationException::class);

        new ConditionGroup(LogicOperator::Or, []);
    }

    /**
     * The other route to a clause-less group is now closed too: an unmapped
     * field is refused rather than skipped, so "" is unreachable in practice
     * and the `''` guard on the return is a belt-and-braces invariant rather
     * than a live branch.
     */
    public function testAGroupOfOnlyUnmappedFieldsIsRefused(): void
    {
        $group = new ConditionGroup(LogicOperator::Or, [
            new Condition('not_a_column', ComparisonOperator::Gt, 0),
        ]);

        $this->expectException(QueryValidationException::class);

        $this->compile($group, $this->columnMap());
    }

    public function testNestedGroupsRemainCorrectlyGrouped(): void
    {
        $group = new ConditionGroup(LogicOperator::And, [
            new Condition('status', ComparisonOperator::Eq, 'active'),
            new ConditionGroup(LogicOperator::Or, [
                new Condition('id', ComparisonOperator::Gt, 0),
                new Condition('form_id', ComparisonOperator::Gt, 0),
            ]),
        ]);

        self::assertSame(
            '(e.status = %s AND (e.id > %s OR e.form_id > %s))',
            $this->compile($group, $this->columnMap())['clause']
        );
    }

    /**
     * Count ORs that sit outside every parenthesised span.
     */
    private function topLevelOrCount(string $sql): int
    {
        $depth = 0;
        $count = 0;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            } elseif ($depth === 0 && strtoupper(substr($sql, $i, 4)) === ' OR ') {
                $count++;
            }
        }

        return $count;
    }
}
