<?php

namespace DataKit\DataViews\Data;

use DataKit\DataViews\Data\Exception\DataNotFoundException;

/**
 * Represents a mutable data source.
 * @since $ver$
 */
interface MutableDataSource extends DataSource {
	/**
	 * Deletes all data by their ID.
	 *
	 * @param string ...$ids The ids of the data sets to delete..
	 *
	 * @throws DataNotFoundException When the data was not found.
	 */
	public function delete_data_by_id( string ...$ids ) : void;
}
