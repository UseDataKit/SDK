<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress;

use DataKit\DataViews\Query\TimeBucket;

/**
 * Compiles TimeBucket enums to MySQL DATE_FORMAT expressions.
 *
 * Shared across all WordPress SQL backends.
 *
 * @since $ver$
 */
final class SqlTimeBucketCompiler
{
    /**
     * Compile a time bucket expression for MySQL.
     *
     * @param string     $colExpr The SQL column expression for the datetime field.
     * @param TimeBucket $grain   The time bucket granularity.
     *
     * @return string The MySQL expression.
     */
    public function compile(string $colExpr, TimeBucket $grain): string
    {
        return match ($grain) {
            TimeBucket::Hour => "DATE_FORMAT({$colExpr}, '%%Y-%%m-%%d %%H:00:00')",
            TimeBucket::Day => "DATE_FORMAT({$colExpr}, '%%Y-%%m-%%d')",
            TimeBucket::Week => "DATE_FORMAT(DATE_SUB({$colExpr}, INTERVAL WEEKDAY({$colExpr}) DAY), '%%Y-%%m-%%d')",
            TimeBucket::Month => "DATE_FORMAT({$colExpr}, '%%Y-%%m-01')",
            TimeBucket::Quarter => "CONCAT(YEAR({$colExpr}), '-Q', QUARTER({$colExpr}))",
            TimeBucket::Year => "DATE_FORMAT({$colExpr}, '%%Y-01-01')",
        };
    }
}
