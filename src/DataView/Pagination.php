<?php

namespace DataKit\DataViews\DataView;

use DataKit\DataViews\Data\DataSource;

/**
 * Represents the pagination settings of a dataview.
 *
 * @since $ver$
 */
final class Pagination {
	/**
	 * The current page.
	 *
	 * @since $ver$
	 * @var int|mixed
	 */
	private int $page;

	/**
	 * The amount of results per page.
	 *
	 * @since $ver$
	 * @var int|mixed
	 */
	private int $per_page;

	/**
	 * Creates the pagination object.
	 *
	 * @since $ver$
	 *
	 * @param int      $page     The current page.
	 * @param int|null $per_page The amount of results per page.
	 */
	public function __construct( int $page, ?int $per_page = null ) {
		$this->page     = max( 1, $page );
		$this->per_page = max( 1, $per_page ?? 25 );
	}

	/**
	 * Creates an instance from an array.
	 *
	 * @since $ver$
	 *
	 * @param array $array The array.
	 *
	 * @return self A pagination instance.
	 */
	public static function from_array( array $array ) : self {
		if ( ! isset( $array['per_page'], $array['page'] ) ) {
			throw new \InvalidArgumentException( 'No page and per page provided.' );
		}

		return new self( (int) $array['page'], (int) $array['per_page'] );
	}

	/**
	 * Returns an instance with the default settings.
	 *
	 * @since $ver$
	 * @return self A pagination object.
	 */
	public static function default() : self {
		return new self( 1 );
	}

	/**
	 * Returns the limit for a DataSource query.
	 *
	 * @since $ver$
	 * @return int The limit.
	 */
	public function limit() : int {
		return $this->per_page;
	}

	/**
	 * Returns the offset based on the current page.
	 *
	 * @since $ver$
	 * @return int The offset.
	 */
	public function offset() : int {
		return ( $this->page - 1 ) * $this->per_page;
	}

	/**
	 * Returns the JSON data for the `paginationInfo` key on a DataView.
	 *
	 * @since $ver$
	 *
	 * @param DataSource $data_source The data source to use for the info.
	 *
	 * @return array The JSON data.
	 */
	public function info( DataSource $data_source ) : array {
		$total = $data_source->count();

		return [
			'totalItems' => $total,
			'totalPages' => ceil( $total / $this->per_page ),
		];
	}

	/**
	 * Returns the current page.
	 *
	 * @since $ver$
	 * @return int The current page.
	 */
	public function page() : int {
		return $this->page;
	}
}
