<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Exception;

/**
 * Base exception for all query-layer errors.
 *
 * All query exceptions serialize to {code, message, context} for AI-actionable error responses.
 *
 * @since $ver$
 */
abstract class QueryException extends \RuntimeException
{
    protected array $context = [];

    public function toArray(): array
    {
        return [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'context' => $this->context,
        ];
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
