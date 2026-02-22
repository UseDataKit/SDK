<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * Column data types for schema description and result hydration.
 *
 * @since $ver$
 */
enum ColumnType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Float = 'float';
    case Boolean = 'boolean';
    case Datetime = 'datetime';
}
