<?php

namespace DataKit\DataView\DataView;

use DataKit\DataView\Data\DataSource;
use DataKit\DataView\Field\Field;

abstract class DataView {
	protected View $view;
	protected array $fields = [];
	protected DataSource $data_source;
	protected ?Sort $sort = null;
	protected ?Filters $filters = null;
	protected int $page = 1;
	protected int $per_page = 100;

	protected function __construct(
		array $fields,
		DataSource $data_source,
		?Sort $sort = null,
		?Filters $filters = null,
		int $page = 1,
		int $per_page = 100
	) {
		$this->per_page    = $per_page;
		$this->page        = $page;
		$this->filters     = $filters;
		$this->sort        = $sort;
		$this->data_source = $data_source;
		if ( $page < 1 ) {
			$this->page = 1;
		}

		$this->ensure_valid_fields( ...$fields );
	}

	/**
	 * @return static
	 */
	public static function create(
		array $fields,
		DataSource $data_source,
		?Sort $sort = null,
		?Filters $filters = null
	) {
		if ( $sort ) {
			$data_source = $data_source->sort_by( $sort );
		}

		if ( $filters ) {
			$data_source = $data_source->filter_by( $filters );
		}

		return new static(
			$fields,
			$data_source,
			$sort,
			$filters,
		);
	}

	public static function table(
		array $fields,
		DataSource $data_source,
		?Sort $sort = null,
		?Filters $filters = null,
	) : TableDataView {
		return TableDataView::create(
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
	protected function ensure_valid_fields( Field ...$fields ) : void {
		$this->fields = array_merge( $this->fields, $fields );
	}

	/**
	 * @return Field[]
	 */
	public function fields() : array {
		return $this->fields;
	}

	public function sort() : ?Sort {
		return $this->sort;
	}

	public function view() : View {
		return $this->view;
	}

	public function data_source() : DataSource {
		return $this->data_source;
	}

	public function page() : int {
		return $this->page;
	}

	public function per_page() : int {
		return $this->per_page;
	}

	public function filters() : ?Filters {
		return $this->filters;
	}
}
