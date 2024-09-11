<?php

namespace DataKit\DataViews\Data;

use DataKit\DataViews\Cache\CacheProvider;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Search;
use DataKit\DataViews\DataView\Sort;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

/**
 * A data sources that wraps a different data source in a cache layer.
 *
 * Note: This class is used internally. When registering your data source,
 * you do not need to wrap the instance yourself. This will be done by DataKit.
 *
 * @since    $ver$
 * @internal This class is subject to change.
 */
final class CachedDataSource extends BaseDataSource implements MutableDataSource {
	/**
	 * The data source to cache.
	 *
	 * @since $ver$
	 *
	 * @var DataSource
	 */
	private DataSource $inner;

	/**
	 * The caching provider.
	 *
	 * @since $ver$
	 *
	 * @var CacheProvider
	 */
	private CacheProvider $cache;

	/**
	 * Creates a cached data source decorator.
	 *
	 * @since $ver$
	 *
	 * @param DataSource    $inner The wrapped data source.
	 * @param CacheProvider $cache The cache provider .
	 */
	public function __construct( DataSource $inner, CacheProvider $cache ) {
		$this->inner = $inner;
		$this->cache = $cache;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function id(): string {
		return $this->inner->id();
	}

	/**
	 * Returns the tags used for this data source.
	 *
	 * @since $ver$
	 *
	 * @return string[] The tags.
	 */
	private function get_tag_keys(): array {
		return [ 'DATASOURCE_' . $this->get_cache_key() ];
	}

	/**
	 * Returns a calculated key based on a set of arguments.
	 *
	 * @since $ver$
	 *
	 * @param mixed ...$arguments any scalar arguments used calculate a unique cache key.
	 *
	 * @return string @return string The cache key.
	 */
	private function get_cache_key( ...$arguments ): string {
		$arguments[] = $this->inner->id();

		try {
			return md5( json_encode( array_values( array_filter( $arguments ) ), JSON_THROW_ON_ERROR ) );
		} catch ( JsonException $e ) {
			throw new InvalidArgumentException(
				'The cache key could not be generated based on the provided arguments.',
				$e->getCode(),
				$e,
			);
		}
	}

	/**
	 * Returns a calculated key based on a set of arguments and the inner data source and filters.
	 *
	 * @since $ver$
	 *
	 * @param mixed ...$arguments any scalar arguments used calculate a unique cache key.
	 *
	 * @return string The cache key.
	 */
	private function get_filter_aware_cache_key( ...$arguments ): string {
		$arguments[] = $this->filters ? $this->filters->to_array() : null;
		$arguments[] = (string) $this->search;

		return $this->get_cache_key( ...$arguments );
	}

	/**
	 * Retrieves the result from the cache, or stores if it does not exist.
	 *
	 * @since $ver$
	 *
	 * @param string   $cache_key       The cache key.
	 * @param callable $retrieve_result The callback that provides the result to cache.
	 *
	 * @return mixed The cached result.
	 */
	private function fetch( string $cache_key, callable $retrieve_result ) {
		$result = $this->cache->get( $cache_key );

		if ( null !== $result ) {
			return $result;
		}

		$result = $retrieve_result();

		$this->cache->set( $cache_key, $result, null, $this->get_tag_keys() );

		return $result;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get_data_ids( int $limit = 20, int $offset = 0 ): array {
		$key = $this->get_filter_aware_cache_key(
			__FUNCTION__,
			$this->sort ? $this->sort->to_array() : null,
			$limit,
			$offset
		);

		return $this->fetch(
			$key,
			function () use ( $limit, $offset ) {
				return $this->inner->get_data_ids( $limit, $offset );
			},
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get_data_by_id( string $id ): array {
		$key = $this->get_cache_key( __FUNCTION__, $id );

		return $this->fetch(
			$key,
			function () use ( $id ) {
				return $this->inner->get_data_by_id( $id );
			},
		);
	}

	/**
	 * @inheritDoc
	 *
	 * Note: this method is not cached, as it should always show the actual fields.
	 *
	 * @since $ver$
	 */
	public function get_fields(): array {
		return $this->inner->get_fields();
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function count(): int {
		$key = $this->get_filter_aware_cache_key( __FUNCTION__ );

		return $this->fetch( $key, fn() => $this->inner->count() );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function filter_by( ?Filters $filters ): self {
		$cached        = parent::filter_by( $filters );
		$cached->inner = $this->inner->filter_by( $filters );

		return $cached;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function search_by( ?Search $search ): self {
		$cached        = parent::search_by( $search );
		$cached->inner = $this->inner->search_by( $search );

		return $cached;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function sort_by( ?Sort $sort ): self {
		$cached = parent::sort_by( $sort );

		if ( ! $cached instanceof self ) {
			throw new RuntimeException( 'Wrong data source provided' );
		}

		$cached->inner = $this->inner->sort_by( $sort );

		return $cached;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function can_delete(): bool {
		if ( ! $this->inner instanceof MutableDataSource ) {
			return false;
		}

		return $this->inner->can_delete();
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function delete_data_by_id( string ...$ids ): void {
		if ( ! $this->inner instanceof MutableDataSource ) {
			return;
		}

		$this->inner->delete_data_by_id( ...$ids );

		$this->clear_cache();
	}

	/**
	 * Clears the underlying cache for this data source.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether the cache was cleared.
	 */
	public function clear_cache(): bool {
		return $this->cache->delete_by_tags( $this->get_tag_keys() );
	}
}
