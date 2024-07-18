<?php

namespace DataKit\DataViews\Cache;

/**
 * Represents a cache provider.
 *
 * @since $ver$
 */
interface CacheProvider {
	/**
	 * Sets the cache for a key with an optional time to live (ttl) in seconds.
	 *
	 * @param string   $key   The key.
	 * @param mixed    $value The value to cache.
	 * @param int|null $ttl   The time to live in seconds.
	 */
	public function set( string $key, $value, ?int $ttl = null ): void;

	/**
	 * Retrieves the value from the cache, or the default if no value is stored.
	 *
	 * @since $ver$
	 *
	 * @param string $key      The key.
	 * @param mixed  $fallback The default in case of a missing (or expired) cache.
	 *
	 * @return mixed
	 */
	public function get( string $key, $fallback = null );

	/**
	 * Whether the cache contains a value for the provided key.
	 *
	 * NOTE: Use this only for priming a cache, as this method is subject to a race condition
	 * where the subsequent `get()` call no longer contains a valid cache.
	 *
	 * @since $ver$
	 *
	 * @param string $key The cache key.
	 *
	 * @return bool Whether the cache contains the current key.
	 */
	public function has( string $key ): bool;

	/**
	 * Deletes the cache for a key.
	 *
	 * @since $ver$
	 *
	 * @param string $key The cache key.
	 *
	 * @return bool Whether the value was deleted.
	 */
	public function delete( string $key ): bool;

	/**
	 * Clears the entire cache.
	 *
	 * @since $ver$
	 * @return bool Whether the clearing of the cache was successful.
	 */
	public function clear(): bool;
}
