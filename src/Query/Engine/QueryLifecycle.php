<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

use DataKit\DataViews\Query\Query;

/**
 * Lifecycle hooks for query execution.
 *
 * Implementations can modify the query/compiled query at each stage.
 *
 * @since $ver$
 */
interface QueryLifecycle
{
    /**
     * Called before query compilation. May return a modified query.
     */
    public function beforeCompile(Query $query): Query;

    /**
     * Called after compilation. May return a modified compiled query.
     */
    public function afterCompile(CompiledQuery $compiled): CompiledQuery;

    /**
     * Called before execution. May return a modified compiled query.
     */
    public function beforeExecute(CompiledQuery $compiled): CompiledQuery;

    /**
     * Called after successful execution. May return a modified result.
     */
    public function afterExecute(Result $result): Result;

    /**
     * Called when an error occurs during execution.
     */
    public function onError(\Throwable $e, Query $query): void;
}
