<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * Aggregate functions for metrics computation.
 *
 * @since $ver$
 */
enum AggregateFunction: string
{
    case Count = 'count';
    case CountDistinct = 'count_distinct';
    case Sum = 'sum';
    case Avg = 'avg';
    case Min = 'min';
    case Max = 'max';
}
