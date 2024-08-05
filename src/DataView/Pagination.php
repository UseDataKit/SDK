<?php

namespace DataKit\DataViews\DataView;

use DataKit\DataViews\Data\DataSource;

/**
 * Represents the pagination settings of a DataView.
 *
 * @link  https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#paginationinfo-object
 *
 * @since $ver$
 */
final class Pagination {
	/**
	 * The current page.
	 *
	 * @since $ver$
	 *
	 * @var int
	 */
	private int $page;

	/**
	 * The amount of results per page.
	 *
	 * @since $ver$
	 *
	 * @var int
	 */
	private int $per_page;

	/**
	 * The default results per page.
	 *
	 * @since $ver$
	 * @var int
	 */
	private static int $default_per_page = 25;

	/**
	 * Creates the pagination object.
	 *
	 * @since $ver$
	 *
	 * @param int      $page     The current page.
	 * @param int|null $per_page The amount of results per page.
	 */
	public function __construct( int $page, ?int $per_page = null ) {
		$per_page ??= self::$default_per_page;

		$this->page     = max( 1, $page );
		$this->per_page = max( 1, $per_page );
	}

	/**
	 * Creates an instance from an array.
	 *
	 * @since $ver$
	 *
	 * @param array $pagination_array The array.
	 *
	 * @return self A pagination instance.
	 */
	public static function from_array( array $pagination_array ): self {
		if ( ! isset( $pagination_array['per_page'], $pagination_array['page'] ) ) {
			throw new \InvalidArgumentException( 'No page and per page provided.' );
		}

		return new self( (int) $pagination_array['page'], (int) $pagination_array['per_page'] );
	}

	/**
	 * Returns an instance with the default settings.
	 *
	 * @since $ver$
	 *
	 * @return self A pagination object.
	 */
	public static function default(): self {
		return new self( 1 );
	}

	/**
	 * Registers the default results per page.
	 *
	 * @since $ver$
	 *
	 * @param int $amount The amount of results per page.
	 */
	public static function default_results_per_page( int $amount ): void {
		self::$default_per_page = max( 1, $amount );
	}

	/**
	 * Returns the limit for a DataSource query.
	 *
	 * @since $ver$
	 *
	 * @return int The limit.
	 */
	public function limit(): int {
		return $this->per_page;
	}

	/**
	 * Returns the offset based on the current page.
	 *
	 * @since $ver$
	 *
	 * @return int The offset.
	 */
	public function offset(): int {
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
	public function info( DataSource $data_source ): array {
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
	 *
	 * @return int The current page.
	 */
	public function page(): int {
		return $this->page;
	}
}
