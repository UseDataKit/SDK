<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

/**
 * Declares what a backend can do. Engine validates queries against these.
 *
 * @since $ver$
 */
enum Capability: string
{
    // Filter capabilities (1:1 with ComparisonOperator)
    case FilterEq = 'filter_eq';
    case FilterNeq = 'filter_neq';
    case FilterGt = 'filter_gt';
    case FilterGte = 'filter_gte';
    case FilterLt = 'filter_lt';
    case FilterLte = 'filter_lte';
    case FilterIn = 'filter_in';
    case FilterNotIn = 'filter_not_in';
    case FilterBetween = 'filter_between';
    case FilterContains = 'filter_contains';
    case FilterNotContains = 'filter_not_contains';
    case FilterStartsWith = 'filter_starts_with';
    case FilterIsEmpty = 'filter_is_empty';
    case FilterIsNotEmpty = 'filter_is_not_empty';

    // Aggregate capabilities
    case AggCount = 'agg_count';
    case AggSum = 'agg_sum';
    case AggAvg = 'agg_avg';
    case AggMin = 'agg_min';
    case AggMax = 'agg_max';
    case AggCountDistinct = 'agg_count_distinct';

    // Structural capabilities
    case GroupBy = 'group_by';
    case Having = 'having';
    case OrConditions = 'or_conditions';
    case TimeBucket = 'time_bucket';
    case Search = 'search';
    case OrderBy = 'order_by';
    case LimitOffset = 'limit_offset';
    case Unnest = 'unnest';

    /**
     * Maps a ComparisonOperator to its corresponding filter capability.
     */
    public static function fromComparisonOperator(\DataKit\DataViews\Query\ComparisonOperator $op): self
    {
        return match ($op) {
            \DataKit\DataViews\Query\ComparisonOperator::Eq => self::FilterEq,
            \DataKit\DataViews\Query\ComparisonOperator::Neq => self::FilterNeq,
            \DataKit\DataViews\Query\ComparisonOperator::Gt => self::FilterGt,
            \DataKit\DataViews\Query\ComparisonOperator::Gte => self::FilterGte,
            \DataKit\DataViews\Query\ComparisonOperator::Lt => self::FilterLt,
            \DataKit\DataViews\Query\ComparisonOperator::Lte => self::FilterLte,
            \DataKit\DataViews\Query\ComparisonOperator::In => self::FilterIn,
            \DataKit\DataViews\Query\ComparisonOperator::NotIn => self::FilterNotIn,
            \DataKit\DataViews\Query\ComparisonOperator::Between => self::FilterBetween,
            \DataKit\DataViews\Query\ComparisonOperator::Contains => self::FilterContains,
            \DataKit\DataViews\Query\ComparisonOperator::NotContains => self::FilterNotContains,
            \DataKit\DataViews\Query\ComparisonOperator::StartsWith => self::FilterStartsWith,
            \DataKit\DataViews\Query\ComparisonOperator::IsEmpty => self::FilterIsEmpty,
            \DataKit\DataViews\Query\ComparisonOperator::IsNotEmpty => self::FilterIsNotEmpty,
        };
    }

    /**
     * Maps an AggregateFunction to its corresponding capability.
     */
    public static function fromAggregateFunction(\DataKit\DataViews\Query\AggregateFunction $fn): self
    {
        return match ($fn) {
            \DataKit\DataViews\Query\AggregateFunction::Count => self::AggCount,
            \DataKit\DataViews\Query\AggregateFunction::CountDistinct => self::AggCountDistinct,
            \DataKit\DataViews\Query\AggregateFunction::Sum => self::AggSum,
            \DataKit\DataViews\Query\AggregateFunction::Avg => self::AggAvg,
            \DataKit\DataViews\Query\AggregateFunction::Min => self::AggMin,
            \DataKit\DataViews\Query\AggregateFunction::Max => self::AggMax,
        };
    }
}
