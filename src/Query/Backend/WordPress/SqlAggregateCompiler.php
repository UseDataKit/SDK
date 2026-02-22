<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;

/**
 * Compiles AggregateFunction enums to MySQL aggregate expressions.
 *
 * Shared across all WordPress SQL backends.
 *
 * @since $ver$
 */
final class SqlAggregateCompiler
{
    /**
     * Compile an aggregate field to a MySQL SELECT expression.
     *
     * @param AggregateField $metric  The aggregate field.
     * @param string|null    $colExpr The SQL column expression (null for COUNT(*)).
     *
     * @return string The MySQL SELECT expression with alias.
     */
    public function compile(AggregateField $metric, ?string $colExpr): string
    {
        $expr = match ($metric->function) {
            AggregateFunction::Count => $colExpr !== null ? "COUNT({$colExpr})" : 'COUNT(*)',
            AggregateFunction::CountDistinct => "COUNT(DISTINCT {$colExpr})",
            AggregateFunction::Sum => "SUM({$colExpr})",
            AggregateFunction::Avg => "AVG({$colExpr})",
            AggregateFunction::Min => "MIN({$colExpr})",
            AggregateFunction::Max => "MAX({$colExpr})",
        };

        $alias = $metric->outputName();

        return "{$expr} AS `{$alias}`";
    }
}
