<?php

namespace DataKit\DataViews\Cache;

use DataKit\DataViews\Clock\Clock;
use DataKit\DataViews\Clock\SystemClock;

/**
 * An abstract cache provider that implements default logic.
 *
 * @since $ver$
 */
abstract class BaseCacheProvider implements CacheProvider {
	/**
	 * The clock.
	 *
	 * @since $ver$
	 * @var Clock
	 */
	protected Clock $clock;

	/**
	 * Creates the base cache provider.
	 *
	 * @since $ver$
	 *
	 * @param Clock|null $clock The clock.
	 */
	public function __construct( ?Clock $clock = null ) {
		$this->clock = $clock ?? new SystemClock();
	}

	/**
	 * Returns the {@see CacheItem} if found by key.
	 *
	 * @param string $key The key.
	 *
	 * @return CacheItem|null The cache item.
	 */
	abstract protected function doGet( string $key ): ?CacheItem;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get( string $key, $fallback = null ) {
		$item = $this->doGet( $key );
		if ( ! $item || $item->is_expired( $this->clock->now() ) ) {
			$this->delete( $key );

			return $fallback;
		}

		return $item->value();
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function has( string $key ): bool {
		$item = $this->doGet( $key );

		if ( ! $item || $item->is_expired( $this->clock->now() ) ) {
			$this->delete( $key );

			return false;
		}

		return true;
	}
}
