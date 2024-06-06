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
	private string $id;
	private string $name;
	private array $data;

	/**
	 * Creates the data source.
	 * @since $ver$
	 *
	 * @param string $id   The ID.
	 * @param string $name The name.
	 * @param array  $data The data.
	 */
	public function __construct(
		string $id,
		string $name,
		array $data = []
	) {
		$this->data = $data;
		$this->name = $name;
		$this->id   = $id;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_ids( int $limit = 100, int $offset = 0 ): array {
		return array_slice( array_keys( $this->get_data() ), $offset, $limit );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_by_id( string $id ): array {
		$result = $this->data[ $id ] ?? null;
		if ( ! $result ) {
			throw new RuntimeException( 'Dataset for id not found.' );
		}

		$result['id'] = $id;

		return $result;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function count(): int {
		return count( $this->get_data() );
	}

	/**
	 * Returns the data, filtered by the {@see Filters}.
	 * @since $ver$
	 * @return array The filtered data.
	 */
	private function get_data(): array {
		$data = $this->data;

		if ( $this->filters ) {
			$data = array_filter(
				$data,
				function ( array $item ): bool {
					$is_match = true;
					foreach ( $this->filters as $filter ) {
						if ( ! $filter->matches( $item ) ) {
							$is_match = false;
							break;
						}
					}

					return $is_match;
				} );
		}

		if ( $this->sort ) {
			$sort    = $this->sort->to_array();
			$is_desc = Sort::DESC === $sort['direction'];
			$field   = $sort['field'];

			uasort( $data, static function ( array $a, array $b ) use ( $is_desc, $field ): int {
				if ( $is_desc ) {
					[ $b, $a ] = [ $a, $b ];
				}

				return strnatcmp( $a[ $field ] ?? '', $b[ $field ] ?? '' );
			} );
		}


		return $data;
	}
}
