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
final class ArrayCacheProvider extends BaseCacheProvider {
	/**
	 * The cached items.
	 *
	 * @since $ver$
	 *
	 * @var CacheItem[]
	 */
	private array $items = [];

	/**
	 * Contains the reference to the tags with their tagged cache keys.
	 *
	 * @since $ver$
	 *
	 * @var array<string, string[]>
	 */
	private array $tags = [];

	/**
	 * Creates an Array cache provider.
	 *
	 * @since $ver$
	 *
	 * @param Clock|null $clock The clock instance.
	 */
	public function __construct( ?Clock $clock = null ) {
		parent::__construct( $clock );

		$this->clock = $clock ?? new SystemClock();
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

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	protected function doGet( string $key ): ?CacheItem {
		return $this->items[ $key ] ?? null;
	}
}
