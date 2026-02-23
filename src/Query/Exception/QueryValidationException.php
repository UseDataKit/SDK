<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Exception;

/**
 * Thrown when a query fails structural validation.
 *
 * @since $ver$
 */
class QueryValidationException extends QueryException
{
    public static function invalidValue(string $field, string $error): self
    {
        $e = new self(sprintf('Invalid value for field "%s": %s', $field, $error));
        $e->context = [
            'field' => $field,
            'error' => $error,
        ];

        return $e;
    }

    public static function emptyConditionGroup(): self
    {
        $e = new self('ConditionGroup must contain at least one condition.');
        $e->context = ['error' => 'empty_condition_group'];

        return $e;
    }

    public static function nestingDepthExceeded(int $maxDepth): self
    {
        $e = new self(sprintf('ConditionGroup nesting depth exceeds maximum of %d.', $maxDepth));
        $e->context = [
            'error' => 'nesting_depth_exceeded',
            'max_depth' => $maxDepth,
        ];

        return $e;
    }

    public static function aggregateRequiresMetricOrDimension(): self
    {
        $e = new self('Aggregate query requires at least one metric or dimension.');
        $e->context = ['error' => 'aggregate_requires_metric_or_dimension'];

        return $e;
    }

    public static function havingOnlyForAggregate(): self
    {
        $e = new self('HAVING clause is only allowed on aggregate queries.');
        $e->context = ['error' => 'having_only_for_aggregate'];

        return $e;
    }

    public static function missingRequiredField(string $field): self
    {
        $e = new self(sprintf('Missing required field: %s', $field));
        $e->context = ['field' => $field, 'error' => 'missing_required_field'];

        return $e;
    }

    public static function invalidStructure(string $detail, ?\Throwable $previous = null): self
    {
        $e = new self(sprintf('Invalid query structure: %s', $detail), 0, $previous);
        $e->context = ['error' => 'invalid_structure', 'detail' => $detail];

        return $e;
    }
}
