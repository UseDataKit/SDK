<?php

namespace DataKit\DataView\DataView;

use DataKit\DataView\Data\DataSource;
use DataKit\DataView\Field\Field;
use JsonException;

/**
 * Todo:
 * - Add support for 'search'.
 * - Add `Actions`
 * - Make a `Fields` collection.
 */
final class DataView {
	private string $id;
	private View $view;
	/** @var Field[] */
	private array $fields = [];
	private DataSource $data_source;
	private ?Sort $sort;
	private ?Filters $filters;
	private string $search = '';
	private int $page = 1;
	private int $per_page = 100;

	/**
	 * Creates the DataView.
	 *
	 * @since $ver$
	 *
	 * @param View $view The View type.
	 * @param string $id The DataView ID.
	 * @param array $fields The fields.
	 * @param DataSource $data_source The data source.
	 * @param Sort|null $sort The sorting.
	 * @param Filters|null $filters The filters.
	 */
	private function __construct(
		View $view,
		string $id,
		array $fields,
		DataSource $data_source,
		?Sort $sort = null,
		?Filters $filters = null
	) {
		$this->id   = $id;
		$this->view = $view;

		$this->filters     = $filters;
		$this->sort        = $sort;
		$this->data_source = $data_source;

		$this->ensure_valid_fields( ...$fields );
	}

	/**
	 * Creates the DataView of the table type.
	 *
	 * @since $ver$
	 *
	 * @param string $id The DataView ID.
	 * @param array $fields The fields.
	 * @param DataSource $data_source The data source.
	 * @param Sort|null $sort The sorting.
	 * @param Filters|null $filters The filters.
	 */
	public static function table(
		string $id,
		array $fields,
		DataSource $data_source,
		?Sort $sort = null,
		?Filters $filters = null
	) : self {
		return new self(
			View::Table(),
			$id,
			$fields,
			$data_source,
			$sort,
			$filters,
		);
	}

	/**
	 * Makes sure the fields are of the correct type.
	 * @since $ver$
	 *
	 * @param Field ...$fields The fields.
	 **/
	private function ensure_valid_fields( Field ...$fields ) : void {
		$this->fields = array_merge( $this->fields, $fields );
	}

	/**
	 * Returns the ID of the DataView.
	 * @since $ver$
	 * @return string The ID.
	 */
	public function id() : string {
		return $this->id;
	}

	/**
	 * Returns the view data object.
	 * @since $ver$
	 * @return array The view data object.
	 */
	private function view() : array {
		return [
			'search'       => $this->search,
			'type'         => (string) $this->view,
			'filters'      => $this->filters ? $this->filters->to_array() : [],
			'perPage'      => $this->per_page,
			'page'         => $this->page,
			'sort'         => $this->sort ? $this->sort->to_array() : [],
			'hiddenFields' => $this->hidden_fields(),
			'layout'       => [],
		];
	}

	/**
	 * Returns a data source with sorting and filters applied.
	 * @since $ver$
	 * @return DataSource The data source.
	 */
	private function data_source() : DataSource {
		return $this->data_source
			->sort_by( $this->sort )
			->filter_by( $this->filters )
			->search_by( $this->search );
	}

	/**
	 * Returns the calculated offset based on the current page.
	 * @since $ver$
	 * @return int The offset.
	 */
	private function offset() : int {
		return ( $this->page - 1 ) * $this->per_page;
	}

	/**
	 * Returns the data object for Data View.
	 * @since $ver$
	 *
	 * @return array The data object.
	 */
	private function data() : array {
		$data_source = $this->data_source();

		$object = [];

		foreach ( $data_source->get_data_ids( $this->per_page, $this->offset() ) as $data_id ) {
			$data = $data_source->get_data_by_id( $data_id );

			foreach ( $this->fields as $field ) {
				$data[ $field->id() ] = $field->value( $data );
			}

			$object[] = $data;
		}

		return $object;
	}

	/**
	 * Returns all the fields object.
	 * @since $ver$
	 * @return array[] The fields as arrays.
	 */
	private function fields() : array {
		$fields = [];

		foreach ( $this->fields as $field ) {
			$fields[] = array_filter(
				$field->toArray(),
				static fn( $value ) => ! is_null( $value ),
			);
		}

		return $fields;
	}

	/**
	 * Returns the paginationInfo object.
	 * @since $ver$
	 * @return array The pagination information.
	 */
	private function pagination_info() : array {
		$total = $this->data_source()->count();

		return [
			'totalItems' => $total,
			'totalPages' => ceil( $total / $this->per_page ),
		];
	}

	/**
	 * Returns the supportedLayouts object.
	 * @since $ver$
	 * @return string[] The supported layouts.
	 * @todo  provide option to add more.
	 */
	private function supported_layouts() : array {
		return [ (string) $this->view ];
	}

	/**
	 * @return string[] The field ID's.
	 */
	private function hidden_fields() : array {
		$hidden_fields = [];
		foreach ( $this->fields as $field ) {
			if ( ! $field->is_hidden() ) {
				continue;
			}
			$hidden_fields[] = $field->id();
		}

		return $hidden_fields;
	}


	public function with_filters( ?Filters $filters ) : self {
		$clone          = clone $this;
		$clone->filters = $filters;

		return $clone;
	}

	public function with_pagination( int $page, int $per_page = null ) : self {
		$clone       = clone $this;
		$clone->page = max( 1, $page );
		if ( $per_page > 0 ) {
			$clone->per_page = $per_page;
		}

		return $clone;
	}

	public function with_search( string $search ) : self {
		$clone         = clone $this;
		$clone->search = $search;

		return $clone;
	}

	public function with_sort( ?Sort $sort ) : self {
		$clone       = clone $this;
		$clone->sort = $sort;

		return $clone;
	}

	/**
	 * Returns the data needed to set up a
	 * @return array
	 */
	public function to_array() : array {
		return [
			'supportedLayouts' => $this->supported_layouts(),
			'paginationInfo'   => $this->pagination_info(),
			'view'             => $this->view(),
			'fields'           => $this->fields(),
			'data'             => $this->data(),
		];
	}

	/**
	 * Returns the javascript object for a dataview.
	 * @since $ver$
	 * @return string The javascript object.
	 */
	public function to_js( bool $is_pretty = false ) : string {
		$flags = JSON_THROW_ON_ERROR;
		if ( $is_pretty ) {
			$flags |= JSON_PRETTY_PRINT;
		}

		try {
			return preg_replace_callback(
				'/\"__RAW__(.*?)__ENDRAW__\"/s',
				static fn( array $matches ) : string => stripcslashes( $matches[1] ),
				json_encode( $this->to_array(), $flags )
			);
		} catch ( JsonException $e ) {
			return '';
		}
	}
}
