<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

use DataKit\DataViews\DataView\Operator;

/**
 * Comparison operators for query conditions.
 *
 * Bridges to/from the DataView {@see Operator} class where operators overlap.
 *
 * @since $ver$
 */
enum ComparisonOperator: string
{
    case Eq = 'eq';
    case Neq = 'neq';
    case Gt = 'gt';
    case Gte = 'gte';
    case Lt = 'lt';
    case Lte = 'lte';
    case In = 'in';
    case NotIn = 'not_in';
    case Between = 'between';
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case StartsWith = 'starts_with';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';

    /**
     * Bridge from SDK DataView Operator.
     *
     * @throws \InvalidArgumentException For isAll/isNotAll which have no query equivalent.
     */
    public static function fromOperator(Operator $op): self
    {
        return match ((string) $op) {
            'is' => self::Eq,
            'isNot' => self::Neq,
            'isAny' => self::In,
            'isNone' => self::NotIn,
            'isAll', 'isNotAll' => throw new \InvalidArgumentException(
                sprintf('Operator "%s" has no query-layer equivalent.', (string) $op)
            ),
            default => throw new \InvalidArgumentException(
                sprintf('Unknown operator "%s".', (string) $op)
            ),
        };
    }

    /**
     * Bridge to SDK DataView Operator.
     *
     * @return Operator|null Null if no UI equivalent exists.
     */
    public function toOperator(): ?Operator
    {
        return match ($this) {
            self::Eq => Operator::is(),
            self::Neq => Operator::isNot(),
            self::In => Operator::isAny(),
            self::NotIn => Operator::isNone(),
            default => null,
        };
    }

    /**
     * Whether this operator requires an array value.
     */
    public function requiresArray(): bool
    {
        return match ($this) {
            self::In, self::NotIn, self::Between => true,
            default => false,
        };
    }

    /**
     * Whether this operator requires no value (null).
     */
    public function requiresNull(): bool
    {
        return match ($this) {
            self::IsEmpty, self::IsNotEmpty => true,
            default => false,
        };
    }
}
