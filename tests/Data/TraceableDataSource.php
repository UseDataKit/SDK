<?php

namespace DataKit\DataViews\Tests\Data;

use DataKit\DataViews\Data\DataSource;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Sort;

/**
 * Data source that tracks method calls. Used for testing purposes.
 *
 * NOTE: Mutable by design.
 *
 * @since $ver$
 */
final class TraceableDataSource implements DataSource {

	private DataSource $inner;
	private array $calls = [];

	public function __construct( DataSource $inner ) {
		$this->inner = $inner;
	}

	public function id() : string {
		return $this->inner->id();
	}


	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_by_id( string $id ) : array {
		$this->calls[] = [ __METHOD__, ...func_get_args() ];

		return $this->inner->get_data_by_id( $id );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_fields() : array {
		$this->calls[] = [ __METHOD__ ];

		return $this->inner->get_fields();
	}

	public function get_data_ids( int $limit = 20, int $offset = 0 ) : array {
		$this->calls[] = [ __METHOD__, ...func_get_args() ];

		return $this->inner->get_data_ids( $limit, $offset );
	}

	public function filter_by( ?Filters $filters ) : self {
		$this->inner = $this->inner->filter_by( $filters );

		return $this;
	}

	public function sort_by( ?Sort $sort ) : self {
		$this->inner = $this->inner->sort_by( $sort );

		return $this;
	}

	public function search_by( string $search ) : self {
		$this->inner = $this->inner->search_by( $search );

		return $this;
	}

	public function get_calls() : array {
		return $this->calls;
	}

	public function reset() : void {
		$this->calls = [];
	}

	public function count() : int {
		$this->calls[] = [ __METHOD__ ];

		return $this->inner->count();
	}


}
