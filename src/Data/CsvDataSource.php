<?php

namespace DataKit\DataViews\Data;

use ArrayIterator;
use CallbackFilterIterator;
use Closure;
use DataKit\DataViews\Data\DataMatcher\ArrayDataMatcher;
use DataKit\DataViews\DataView\Sort;
use InvalidArgumentException;
use Iterator;
use LimitIterator;
use SplFileObject;

/**
 * Data source backed by a CSV file.
 *
 * @since $ver$
 */
final class CsvDataSource extends BaseDataSource {
	/**
	 * The CSV file iterator.
	 *
	 * @since $ver$
	 */
	private SplFileObject $file;

	/**
	 * Creates the CSV Data Source.
	 *
	 * @since $ver$
	 *
	 * @param string $file_path The file path to the CSV file.
	 */
	public function __construct(
		string $file_path,
		string $separator = ',',
		string $enclosure = '"',
		string $escape = '\\'
	) {
		if (
			! file_exists( $file_path )
			|| ! is_readable( $file_path ) ) {
			throw new InvalidArgumentException( 'The CSV data source file is not found or readable.' );
		}

		$this->file = new SplFileObject( $file_path, 'rb' );
		$this->file->setFlags(
			SplFileObject::READ_CSV |
			SplFileObject::READ_AHEAD |
			SplFileObject::SKIP_EMPTY |
			SplFileObject::DROP_NEW_LINE,
		);
		$this->file->setCsvControl( $separator, $enclosure, $escape );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function id() : string {
		return sprintf( 'csv-%s', $this->file->getBasename() );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_ids( int $limit = 100, int $offset = 0 ) : array {
		$lines = new LimitIterator( $this->data(), $offset, $limit );

		$ids = [];
		foreach ( $lines as $key => $_ ) {
			$ids[] = (string) $key;
		}

		return $ids;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_by_id( string $id ) : array {
		foreach ( $this->data() as $key => $data ) {
			if ( (string) $key !== $id ) {
				continue;
			}

			return $this->cleanup( $data );
		}

		return [];
	}


	/**
	 * Clean the values from the CSV.
	 *
	 * @since $ver$
	 *
	 * @param array $data The data for a single result.
	 *
	 * @return array The cleaned data.
	 */
	private function cleanup( array $data ) : array {
		return array_map( 'trim', $data );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function count() : int {
		$count = iterator_count( $this->data() );

		return $count < 1 ? 0 : $count;
	}

	/**
	 * Lazy instantiate a file object.
	 *
	 * @since $ver$
	 * @return SplFileObject The file object.
	 */
	private function file() : SplFileObject {
		$this->file->rewind();

		return $this->file;
	}

	/**
	 * Returns an iterator that skips the first row to remove the fields.
	 *
	 * @since $ver$
	 * @return Iterator The data iterator.
	 */
	private function data() : Iterator {
		$data = new CallbackFilterIterator(
			new LimitIterator( $this->file(), 1 ),
			Closure::fromCallable( [ $this, 'is_matched_data' ] ),
		);

		if ( $this->sort ) {
			$sort    = $this->sort->to_array();
			$is_desc = Sort::DESC === $sort['direction'];
			$field   = $sort['field'];

			$data = new ArrayIterator( iterator_to_array( $data ) );

			$data->uasort( static function ( array $a, array $b ) use ( $is_desc, $field ) : int {
				if ( $is_desc ) {
					[ $b, $a ] = [ $a, $b ];
				}

				return strnatcmp( $a[ $field ] ?? '', $b[ $field ] ?? '' );
			} );
		}

		return $data;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_fields() : array {
		return $this->file()->current() ?? [];
	}

	/**
	 * Returns whether the data matches the provided filters if provided.
	 *
	 * @since $ver$
	 *
	 * @param array $data The data for a single result.
	 *
	 * @return bool Whether the data matches the filters.
	 */
	private function is_matched_data( array $data ) : bool {
		if ( ! $this->search && ! $this->filters ) {
			return true;
		}

		$data = $this->cleanup( $data );

		if ( $this->search && ! ArrayDataMatcher::is_data_matched_by_string( $data, $this->search ) ) {
			return false;
		}

		if ( $this->filters && ! $this->filters->match( $data ) ) {
			return false;
		}

		return true;
	}
}
