<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress;

use DataKit\DataViews\Query\Backend\WordPress\SqlFilterCompiler;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use PHPUnit\Framework\TestCase;

/**
 * Tests for shared SQL filter compilation (no WordPress needed).
 */
final class SqlFilterCompilerTest extends TestCase
{
    private SqlFilterCompiler $compiler;
    private array $columnMap = [
        'status' => 'e.status',
        'amount' => 'm1.meta_value',
        'name' => 'm2.meta_value',
        'date' => 'e.date_created',
    ];

    protected function setUp(): void
    {
        $this->compiler = new SqlFilterCompiler();
    }

    public function test_eq(): void
    {
        $group = ConditionGroup::and(new Condition('status', ComparisonOperator::Eq, 'active'));
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('e.status = %s', $result['clause']);
        self::assertSame(['active'], $result['params']);
    }

    public function test_neq(): void
    {
        $group = ConditionGroup::and(new Condition('status', ComparisonOperator::Neq, 'trash'));
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('e.status != %s', $result['clause']);
        self::assertSame(['trash'], $result['params']);
    }

    public function test_gt_lt(): void
    {
        $group = ConditionGroup::and(
            new Condition('amount', ComparisonOperator::Gt, 100),
            new Condition('amount', ComparisonOperator::Lt, 500),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('m1.meta_value > %s AND m1.meta_value < %s', $result['clause']);
        self::assertSame([100, 500], $result['params']);
    }

    public function test_in(): void
    {
        $group = ConditionGroup::and(
            new Condition('status', ComparisonOperator::In, ['active', 'pending', 'processing']),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('e.status IN (%s, %s, %s)', $result['clause']);
        self::assertSame(['active', 'pending', 'processing'], $result['params']);
    }

    public function test_not_in(): void
    {
        $group = ConditionGroup::and(
            new Condition('status', ComparisonOperator::NotIn, ['trash', 'spam']),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('e.status NOT IN (%s, %s)', $result['clause']);
        self::assertSame(['trash', 'spam'], $result['params']);
    }

    public function test_between(): void
    {
        $group = ConditionGroup::and(
            new Condition('amount', ComparisonOperator::Between, [10, 100]),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('m1.meta_value BETWEEN %s AND %s', $result['clause']);
        self::assertSame([10, 100], $result['params']);
    }

    public function test_contains(): void
    {
        $group = ConditionGroup::and(
            new Condition('name', ComparisonOperator::Contains, 'test'),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('m2.meta_value LIKE %s', $result['clause']);
        self::assertSame(['%test%'], $result['params']);
    }

    public function test_not_contains(): void
    {
        $group = ConditionGroup::and(
            new Condition('name', ComparisonOperator::NotContains, 'spam'),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('m2.meta_value NOT LIKE %s', $result['clause']);
        self::assertSame(['%spam%'], $result['params']);
    }

    public function test_starts_with(): void
    {
        $group = ConditionGroup::and(
            new Condition('name', ComparisonOperator::StartsWith, 'Acme'),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('m2.meta_value LIKE %s', $result['clause']);
        self::assertSame(['Acme%'], $result['params']);
    }

    public function test_is_empty(): void
    {
        $group = ConditionGroup::and(
            new Condition('name', ComparisonOperator::IsEmpty, null),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame("(m2.meta_value IS NULL OR m2.meta_value = '')", $result['clause']);
        self::assertSame([], $result['params']);
    }

    public function test_is_not_empty(): void
    {
        $group = ConditionGroup::and(
            new Condition('name', ComparisonOperator::IsNotEmpty, null),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame("(m2.meta_value IS NOT NULL AND m2.meta_value != '')", $result['clause']);
        self::assertSame([], $result['params']);
    }

    public function test_or_conditions(): void
    {
        $group = ConditionGroup::or(
            new Condition('status', ComparisonOperator::Eq, 'active'),
            new Condition('status', ComparisonOperator::Eq, 'pending'),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('e.status = %s OR e.status = %s', $result['clause']);
        self::assertSame(['active', 'pending'], $result['params']);
    }

    public function test_nested_and_or(): void
    {
        $group = ConditionGroup::and(
            new Condition('amount', ComparisonOperator::Gt, 0),
            ConditionGroup::or(
                new Condition('status', ComparisonOperator::Eq, 'active'),
                new Condition('status', ComparisonOperator::Eq, 'pending'),
            ),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('m1.meta_value > %s AND (e.status = %s OR e.status = %s)', $result['clause']);
        self::assertSame([0, 'active', 'pending'], $result['params']);
    }

    public function test_skips_unmapped_fields(): void
    {
        $group = ConditionGroup::and(
            new Condition('unknown_field', ComparisonOperator::Eq, 'test'),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('', $result['clause']);
        self::assertSame([], $result['params']);
    }

    public function test_escape_like_characters(): void
    {
        $group = ConditionGroup::and(
            new Condition('name', ComparisonOperator::Contains, '50% off_sale'),
        );
        $result = $this->compiler->compile($group, $this->columnMap);

        self::assertSame('m2.meta_value LIKE %s', $result['clause']);
        self::assertSame('%50\\% off\\_sale%', $result['params'][0]);
    }
}
