<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress;

use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\LogicOperator;

/**
 * Compiles query conditions to SQL WHERE clauses with prepared parameters.
 *
 * Shared across all WordPress SQL backends.
 *
 * @since $ver$
 */
final class SqlFilterCompiler
{
    /**
     * Compile a ConditionGroup into SQL clause + params.
     *
     * @param ConditionGroup       $group     The condition group.
     * @param array<string,string> $columnMap Field key => SQL expression mapping.
     *
     * @return array{clause: string, params: array}
     */
    public function compile(ConditionGroup $group, array $columnMap): array
    {
        return $this->compileGroup($group, $columnMap);
    }

    private function compileGroup(ConditionGroup $group, array $columnMap): array
    {
        $clauses = [];
        $params = [];
        $glue = $group->logic === LogicOperator::And ? ' AND ' : ' OR ';

        foreach ($group->conditions as $condition) {
            if ($condition instanceof ConditionGroup) {
                $sub = $this->compileGroup($condition, $columnMap);

                if ($sub['clause'] !== '') {
                    $clauses[] = '(' . $sub['clause'] . ')';
                    $params = array_merge($params, $sub['params']);
                }
            } elseif ($condition instanceof Condition) {
                $colExpr = $columnMap[$condition->field] ?? null;

                if ($colExpr === null) {
                    continue;
                }

                $sub = $this->compileCondition($condition, $colExpr);
                $clauses[] = $sub['clause'];
                $params = array_merge($params, $sub['params']);
            }
        }

        return [
            'clause' => implode($glue, $clauses),
            'params' => $params,
        ];
    }

    /**
     * @return array{clause: string, params: array}
     */
    private function compileCondition(Condition $condition, string $colExpr): array
    {
        return match ($condition->operator) {
            ComparisonOperator::Eq => [
                'clause' => "{$colExpr} = %s",
                'params' => [$condition->value],
            ],
            ComparisonOperator::Neq => [
                'clause' => "{$colExpr} != %s",
                'params' => [$condition->value],
            ],
            ComparisonOperator::Gt => [
                'clause' => "{$colExpr} > %s",
                'params' => [$condition->value],
            ],
            ComparisonOperator::Gte => [
                'clause' => "{$colExpr} >= %s",
                'params' => [$condition->value],
            ],
            ComparisonOperator::Lt => [
                'clause' => "{$colExpr} < %s",
                'params' => [$condition->value],
            ],
            ComparisonOperator::Lte => [
                'clause' => "{$colExpr} <= %s",
                'params' => [$condition->value],
            ],
            ComparisonOperator::In => $this->compileIn($colExpr, (array) $condition->value, false),
            ComparisonOperator::NotIn => $this->compileIn($colExpr, (array) $condition->value, true),
            ComparisonOperator::Between => [
                'clause' => "{$colExpr} BETWEEN %s AND %s",
                'params' => [$condition->value[0], $condition->value[1]],
            ],
            ComparisonOperator::Contains => [
                'clause' => "{$colExpr} LIKE %s",
                'params' => ['%' . self::escapeLike((string) $condition->value) . '%'],
            ],
            ComparisonOperator::NotContains => [
                'clause' => "{$colExpr} NOT LIKE %s",
                'params' => ['%' . self::escapeLike((string) $condition->value) . '%'],
            ],
            ComparisonOperator::StartsWith => [
                'clause' => "{$colExpr} LIKE %s",
                'params' => [self::escapeLike((string) $condition->value) . '%'],
            ],
            ComparisonOperator::IsEmpty => [
                'clause' => "({$colExpr} IS NULL OR {$colExpr} = '')",
                'params' => [],
            ],
            ComparisonOperator::IsNotEmpty => [
                'clause' => "({$colExpr} IS NOT NULL AND {$colExpr} != '')",
                'params' => [],
            ],
        };
    }

    /**
     * @return array{clause: string, params: array}
     */
    private function compileIn(string $colExpr, array $values, bool $negate): array
    {
        $placeholders = implode(', ', array_fill(0, count($values), '%s'));
        $op = $negate ? 'NOT IN' : 'IN';

        return [
            'clause' => "{$colExpr} {$op} ({$placeholders})",
            'params' => array_values($values),
        ];
    }

    /**
     * Escape special LIKE characters.
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }
}
