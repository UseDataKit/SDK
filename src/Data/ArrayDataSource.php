<?php

namespace DataKit\DataView\Data;

use DataKit\DataView\DataView\Filters;
use DataKit\DataView\DataView\Sort;
use RuntimeException;

/**
 * A data source backed by a kay/value array.
 * @since $ver$
 */
final class ArrayDataSource extends BaseDataSource {
	/**
	 * Creates the data source.
	 * @since $ver$
	 *
	 * @param string $id The ID.
	 * @param string $name The name.
	 * @param array $data The data.
	 */
	public function __construct(
		private string $id,
		private string $name,
		private array $data = [],
	) {
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function id() : string {
		return $this->id;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function name() : string {
		return $this->name;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_ids( int $limit = 100, int $offset = 0 ) : array {
		return array_slice( array_keys( $this->get_data() ), $offset, $limit );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_by_id( string $id ) : array {
		return $this->data[ $id ] ?? throw new RuntimeException( 'Dataset for id not found.' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function count() : int {
		return count( $this->data );
	}

	/**
	 * Returns the data, filtered by the {@see Filters}.
	 * @since $ver$
	 * @return array The filtered data.
	 */
	private function get_data() : array {
		$data = $this->data;

		if ( $this->filters ) {
			$data = array_filter( $data, function ( array $item ) : bool {
				$matches = true;
				foreach ( $this->filters as $filter ) {
					if ( ! $filter->matches( $item ) ) {
						$matches = false;
						break;
					}
				}

				return $matches;			} );
		}

		if ( $this->sort ) {
			$sort    = $this->sort->toArray();
			$is_desc = Sort::DESC === $sort['direction'];
			$field   = $sort['field'];

			uasort( $data, static function ( array $a, array $b ) use ( $is_desc, $field ) : int {
				if ( $is_desc ) {
					[ $b, $a ] = [ $a, $b ];
				}

				return strnatcmp( $a[ $field ] ?? '', $b[ $field ] ?? '' );
			} );
		}


		return $data;
	}
}
