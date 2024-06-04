<?php

namespace DataKit\DataView\DataView;

use DataKit\DataView\Data\DataSource;

final class TableDataView extends DataView {
	protected function __construct(
		array $fields,
		DataSource $data_source,
		?Sort $sort = null,
		?Filters $filters = null,
		int $page = 1,
		int $per_page = 100
	) {
		parent::__construct( $fields, $data_source, $sort, $filters, $page, $per_page );
		$this->view = View::Table();
	}
}
