<?php

namespace DataKit\DataViews\Cache;

use DataKit\DataViews\Clock\Clock;
use DataKit\DataViews\Clock\SystemClock;

/**
 * Cache provider backed by an array.
 *
 * This cache provider can be useful for tests and composition.
 *
 * @since $ver$
 */
final class ArrayCacheProvider implements CacheProvider {
	/**
	 * The cached items.
	 *
	 * @since $ver$
	 * @var array
	 */
	private array $items;

	/**
	 * The clock instance.
	 *
	 * @since $ver$
	 * @var Clock
	 */
	private Clock $clock;

	/**
	 * Creates an Array Cache Provider.
	 *
	 * @since $ver$
	 *
	 * @param Clock|null $clock The clock instance.
	 * @param array      $items The pre-filled cache items.
	 */
	public function __construct( ?Clock $clock = null, array $items = [] ) {
		$this->clock = $clock ?? new SystemClock();
		$this->items = $items;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function set( string $key, $value, ?int $ttl = null ) : void {
		$time = $ttl
			? ( $this->clock->now()->getTimestamp() + $ttl )
			: null;

		$this->items[ $key ] = compact( 'value', 'time' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get( string $key, $default = null ) {
		$item = $this->items[ $key ] ?? [];

		if ( $this->is_expired( $item ) ) {
			unset( $this->items[ $key ] );

			return $default;
		}

		return $item['value'] ?? $default;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function has( string $key ) : bool {
		if (
			isset( $this->items[ $key ] )
			&& ! $this->is_expired( $this->items[ $key ] )
		) {
			return true;
		}

		unset( $this->items[ $key ] );

		return false;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function delete( string $key ) : bool {
		unset( $this->items[ $key ] );

		return true;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function clear() : bool {
		$this->items = [];

		return true;
	}

	/**
	 * Whether the provided cache item is expired.
	 *
	 * @since $ver$
	 *
	 * @param array $item The cache item.
	 *
	 * @return bool Whether the cache is expired.
	 */
	private function is_expired( array $item ) : bool {
		return (
			( $item['time'] ?? null )
			&& $this->clock->now()->getTimestamp() > $item['time']
		);
	}
}
