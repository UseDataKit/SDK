<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Exception;

/**
 * Thrown when query execution fails at the backend level.
 *
 * @since $ver$
 */
class QueryExecutionException extends QueryException
{
    public static function create(string $queryHash, string $backendError, ?\Throwable $previous = null): self
    {
        $e = new self(
            sprintf('Query execution failed: %s', $backendError),
            0,
            $previous,
        );
        $e->context = [
            'query_hash' => $queryHash,
            'backend_error' => $backendError,
        ];

        return $e;
    }
}
