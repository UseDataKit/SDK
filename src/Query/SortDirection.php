<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * Sort direction for ORDER BY clauses.
 *
 * @since $ver$
 */
enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
