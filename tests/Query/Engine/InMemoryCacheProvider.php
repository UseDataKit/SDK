<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Engine;

use DataKit\DataViews\Query\Engine\CacheProvider;

/**
 * In-memory CacheProvider for testing.
 *
 * Stores entries keyed by the cache key string. The deleteByTag method
 * uses substring matching (same semantics as TransientCacheProvider).
 */
final class InMemoryCacheProvider implements CacheProvider
{
    /** @var array<string, array> */
    private array $store = [];

    public function get(string $key): ?array
    {
        return $this->store[$key] ?? null;
    }

    public function set(string $key, array $data, int $ttl): void
    {
        $this->store[$key] = $data;
    }

    public function delete(string $key): void
    {
        unset($this->store[$key]);
    }

    public function deleteByTag(string $tag): void
    {
        foreach (array_keys($this->store) as $key) {
            if (str_contains($key, $tag)) {
                unset($this->store[$key]);
            }
        }
    }

    /**
     * Return all stored cache keys.
     *
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->store);
    }

    /**
     * Return the number of stored entries.
     */
    public function count(): int
    {
        return count($this->store);
    }
}
