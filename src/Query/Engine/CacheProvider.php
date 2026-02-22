<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

/**
 * Cache interface for query results.
 *
 * @since $ver$
 */
interface CacheProvider
{
    public function get(string $key): ?array;

    public function set(string $key, array $data, int $ttl): void;

    public function delete(string $key): void;

    /**
     * Delete all cached entries associated with a tag.
     *
     * Used for source-specific invalidation (e.g., when a form entry changes).
     */
    public function deleteByTag(string $tag): void;
}
