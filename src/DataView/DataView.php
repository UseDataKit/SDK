<?php

namespace DataKit\DataView\DataView;

use DataKit\DataView\Data\DataSource;
use DataKit\DataView\Field\Field;

abstract class DataView {
	protected View $view;
	protected array $fields = [];

	protected function __construct(
		array $fields,
		protected DataSource $data_source,
		protected ?Sort $sort = null,
		protected ?Filters $filters = null,
		protected $page = 1,
		protected $per_page = 100,
	) {
		$this->add_field( ...$fields );
	}

	public static function create(
		array $fields,
		DataSource $data_source,
		?Sort $sort = null,
		?Filters $filters = null
	) : static {
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

	// Todo : maybe this should be a field collection?
	protected function add_field( Field ...$fields ) : void {
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
