<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * The type of query to execute.
 *
 * @since $ver$
 */
enum QueryType: string
{
    case Browse = 'browse';
    case Aggregate = 'aggregate';
}
