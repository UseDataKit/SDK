<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * Time bucket granularity for time-series aggregation.
 *
 * @since $ver$
 */
enum TimeBucket: string
{
    case Hour = 'hour';
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Quarter = 'quarter';
    case Year = 'year';
}
