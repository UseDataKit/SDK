<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

use DataKit\DataViews\Query\Query;

/**
 * No-op lifecycle — pass-through for all hooks.
 *
 * @since $ver$
 */
final class NullLifecycle implements QueryLifecycle
{
    public function beforeCompile(Query $query): Query
    {
        return $query;
    }

    public function afterCompile(CompiledQuery $compiled): CompiledQuery
    {
        return $compiled;
    }

    public function beforeExecute(CompiledQuery $compiled): CompiledQuery
    {
        return $compiled;
    }

    public function afterExecute(Result $result): Result
    {
        return $result;
    }

    public function onError(\Throwable $e, Query $query): void
    {
        // No-op
    }
}
