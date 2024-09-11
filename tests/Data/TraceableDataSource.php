<?php

namespace DataKit\DataViews\Tests\Data;

use DataKit\DataViews\Data\DataSource;
use DataKit\DataViews\Data\MutableDataSource;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Search;
use DataKit\DataViews\DataView\Sort;

/**
 * Data source that tracks method calls. Used for testing purposes.
 *
 * NOTE: Mutable by design.
 *
 * @since $ver$
 */
final class TraceableDataSource implements MutableDataSource {
	/**
	 * The decorated data source.
	 *
	 * @since $ver$
	 * @var DataSource
	 */
	private DataSource $inner;

	/**
	 * The call list.
	 *
	 * @since $ver$
	 * @var array
	 */
	private array $calls = [];

	/**
	 * Creates the proxy.
	 *
	 * @since $ver$
	 *
	 * @param DataSource $inner The data source to proxy.
	 */
	public function __construct( DataSource $inner ) {
		$this->inner = $inner;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function id() : string {
		// Not tracked by design.
		return $this->inner->id();
	}


	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_by_id( string $id ) : array {
		$this->calls[] = [ __FUNCTION__, ...func_get_args() ];

		return $this->inner->get_data_by_id( $id );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_fields() : array {
		$this->calls[] = [ __FUNCTION__ ];

		return $this->inner->get_fields();
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_ids( int $limit = 20, int $offset = 0 ) : array {
		$this->calls[] = [ __FUNCTION__, ...func_get_args() ];

		return $this->inner->get_data_ids( $limit, $offset );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function filter_by( ?Filters $filters ) : self {
		$this->calls[] = [ __FUNCTION__, ...func_get_args() ];

		$this->inner = $this->inner->filter_by( $filters );

		return $this;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function sort_by( ?Sort $sort ) : self {
		$this->calls[] = [ __FUNCTION__, ...func_get_args() ];

		$this->inner = $this->inner->sort_by( $sort );

		return $this;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function search_by( ?Search $search ) : self {
		$this->calls[] = [ __FUNCTION__, ...func_get_args() ];
		$this->inner   = $this->inner->search_by( $search );

		return $this;
	}

	/**
	 * Returns all performed method calls.
	 *
	 * @since $ver$
	 * @return string[] The method calls.
	 */
	public function get_calls() : array {
		return $this->calls;
	}

	/**
	 * Resets the call list.
	 *
	 * @since $ver$
	 */
	public function reset() : void {
		$this->calls = [];
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function count() : int {
		$this->calls[] = [ __FUNCTION__ ];

		return $this->inner->count();
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function can_delete() : bool {
		if ( ! $this->inner instanceof MutableDataSource ) {
			return false;
		}
		$this->calls[] = [ __FUNCTION__ ];

		return $this->inner->can_delete();
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function delete_data_by_id( string ...$ids ) : void {
		if ( ! $this->inner instanceof MutableDataSource ) {
			return;
		}

		$this->calls[] = [ __FUNCTION__ ];

		$this->inner->delete_data_by_id( ...$ids );
	}
}
