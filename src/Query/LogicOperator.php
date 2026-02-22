<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * Logical operator for combining conditions.
 *
 * @since $ver$
 */
enum LogicOperator: string
{
    case And = 'and';
    case Or = 'or';
}
