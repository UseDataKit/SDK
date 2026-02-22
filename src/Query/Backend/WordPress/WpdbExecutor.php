<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress;

use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\Engine\Result;
use DataKit\DataViews\Query\Exception\QueryExecutionException;

/**
 * Executes compiled SQL queries via WordPress wpdb.
 *
 * Shared across all WordPress SQL backends.
 *
 * @since $ver$
 */
final class WpdbExecutor
{
    private const DEFAULT_TIMEOUT = 30;

    /**
     * Execute a compiled query and return a Result.
     *
     * @param WpdbCompiledQuery      $compiled  The compiled query.
     * @param array<string, ColumnType> $schema Result column schema.
     * @param int                    $timeout   Query timeout in seconds.
     *
     * @throws QueryExecutionException On database errors.
     */
    public function execute(WpdbCompiledQuery $compiled, array $schema, int $timeout = self::DEFAULT_TIMEOUT): Result
    {
        global $wpdb;

        $sql = $compiled->toSql();
        $params = $compiled->getParams();

        // Prepare SQL if there are parameters
        if ($params !== []) {
            $sql = $wpdb->prepare($sql, $params);
        }

        // Set session timeout
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query($wpdb->prepare('SET SESSION MAX_EXECUTION_TIME = %d', $timeout * 1000));

        // Execute
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results($sql, ARRAY_A);

        if ($wpdb->last_error !== '') {
            throw QueryExecutionException::create(
                md5($sql),
                $wpdb->last_error,
            );
        }

        $rows = is_array($rows) ? $rows : [];

        // Get total count (for pagination)
        $totalCount = $this->getTotalCount($compiled, $wpdb, $rows);

        return new Result(
            $schema,
            $rows,
            $totalCount,
            limit: $compiled->limit,
            offset: $compiled->offset > 0 ? $compiled->offset : null,
        );
    }

    /**
     * Get total count by running a COUNT(*) wrapper query.
     * Falls back to row count if count query fails.
     */
    private function getTotalCount(WpdbCompiledQuery $compiled, object $wpdb, array $rows): int
    {
        // If no limit was applied, total count equals row count
        if ($compiled->limit === null) {
            return count($rows);
        }

        // Build count query
        $countSql = 'SELECT COUNT(*) AS total FROM ' . $compiled->from;

        if ($compiled->joins !== []) {
            $countSql .= ' ' . implode(' ', $compiled->joins);
        }

        if ($compiled->where !== []) {
            $countSql .= ' WHERE ' . implode(' AND ', $compiled->where);
        }

        if ($compiled->groupBy !== []) {
            $countSql = 'SELECT COUNT(*) AS total FROM (' . $countSql
                . ' GROUP BY ' . implode(', ', $compiled->groupBy) . ') AS sub';
        }

        $params = $compiled->getParams();

        if ($params !== []) {
            $countSql = $wpdb->prepare($countSql, $params);
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $countResult = $wpdb->get_var($countSql);

        if ($countResult !== null) {
            return (int) $countResult;
        }

        // Fallback
        return count($rows);
    }
}
