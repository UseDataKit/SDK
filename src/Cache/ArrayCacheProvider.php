<?php

namespace DataKit\DataViews\Cache;

use DataKit\DataViews\Clock\Clock;
use DataKit\DataViews\Clock\SystemClock;
use DateInterval;
use Exception;

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
	 *
	 * @var CacheItem[]
	 */
	private array $items;

	/**
	 * Contains the reference to the tags with their tagged cache keys.
	 *
	 * @since $ver$
	 *
	 * @var array<string, string[]>
	 */
	private array $tags = [];

	/**
	 * The clock instance.
	 *
	 * @since $ver$
	 *
	 * @var Clock
	 */
	private Clock $clock;

	/**
	 * Creates an Array cache provider.
	 *
	 * @since $ver$
	 *
	 * @param Clock|null  $clock The clock instance.
	 * @param CacheItem[] $items The pre-filled cache items.
	 */
	public function __construct( ?Clock $clock = null, array $items = [] ) {
		$this->clock = $clock ?? new SystemClock();
		$this->items = $items;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function set( string $key, $value, ?int $ttl = null, array $tags = [] ): void {
		try {
			$time = (int) $ttl > 0
				? ( $this->clock->now()->add( new DateInterval( 'PT' . $ttl . 'S' ) ) )
				: null;
		} catch ( Exception $e ) {
			throw new \InvalidArgumentException( $e->getMessage(), $e->getCode(), $e );
		}

		$this->items[ $key ] = new CacheItem( $key, $value, $time, $tags );

		$this->add_tags( $key, $tags );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get( string $key, $fallback = null ) {
		$item = $this->items[ $key ] ?? null;

		if ( ! $item || $item->is_expired( $this->clock ) ) {
			unset( $this->items[ $key ] );

			return $fallback;
		}

		return $item->value();
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function has( string $key ): bool {
		if (
			isset( $this->items[ $key ] )
			&& ! $this->items[ $key ]->is_expired( $this->clock )
		) {
			return true;
		}

		unset( $this->items[ $key ] );

		return false;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function delete( string $key ): bool {
		unset( $this->items[ $key ] );

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function delete_by_tags( array $tags ): bool {
		foreach ( $tags as $tag ) {
			foreach ( $this->tags[ $tag ] ?? [] as $key ) {
				$this->delete( $key );
			}

			unset( $this->tags[ $tag ] );
		}

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function clear(): bool {
		$this->items = [];
		$this->tags  = [];

		return true;
	}

	/**
	 * Records a key for all provided tags.
	 *
	 * @since $ver$
	 *
	 * @param string $key  The key to tag.
	 * @param array  $tags The tags.
	 */
	private function add_tags( string $key, array $tags ): void {
		foreach ( $tags as $tag ) {
			if ( ! is_string( $tag ) ) {
				throw new \InvalidArgumentException( 'A tag must be a string.' );
			}

			$this->tags[ $tag ] ??= [];

			$this->tags[ $tag ] = array_unique( array_merge( $this->tags[ $tag ], [ $key ] ) );
		}
	}
}
