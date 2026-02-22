<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Exception;

/**
 * Thrown when a query's estimated cost exceeds the configured threshold.
 *
 * @since $ver$
 */
class CostThresholdExceededException extends QueryException
{
    public static function create(array $estimate, array $suggestions = []): self
    {
        $e = new self('Query cost exceeds threshold.');
        $e->context = [
            'estimate' => $estimate,
            'suggestions' => $suggestions,
        ];

        return $e;
    }
}
