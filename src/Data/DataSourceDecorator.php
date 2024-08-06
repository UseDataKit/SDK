<?php

namespace DataKit\DataViews\Data;

use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Search;
use DataKit\DataViews\DataView\Sort;

/**
 * A base class to facilitate the creation decorators or proxies.
 *
 * @since $ver$
 */
abstract class DataSourceDecorator implements DataSource, MutableDataSource {
	/**
	 * Should return the decorated (or proxy) data source.
	 *
	 * Note: This method is called for every implemented function call, meaning it can be used to lazily
	 * instantiate the decorator. This also means you should memoize the instance on your own class to
	 * avoid multiple instantiations.
	 *
	 * @since $ver$
	 *
	 * @return DataSource The data source to decorate or proxy.
	 */
	abstract protected function decorated_datasource(): DataSource;


	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get_data_ids( int $limit = 20, int $offset = 0 ): array {
		return $this->decorated_datasource()->get_data_ids( $limit, $offset );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get_data_by_id( string $id ): array {
		return $this->decorated_datasource()->get_data_by_id( $id );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get_fields(): array {
		return $this->decorated_datasource()->get_fields();
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function count(): int {
		return $this->decorated_datasource()->count();
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function filter_by( ?Filters $filters ) {
		return $this->decorated_datasource()->filter_by( $filters );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function search_by( ?Search $search ) {
		return $this->decorated_datasource()->search_by( $search );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function sort_by( ?Sort $sort ) {
		return $this->decorated_datasource()->sort_by( $sort );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function can_delete(): bool {
		$inner = $this->decorated_datasource();

		if ( ! $inner instanceof MutableDataSource ) {
			return false;
		}

		return $inner->can_delete();
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function delete_data_by_id( string ...$ids ): void {
		$inner = $this->decorated_datasource();

		if ( $inner instanceof MutableDataSource ) {
			$inner->delete_data_by_id( ...$ids );
		}
	}
}
