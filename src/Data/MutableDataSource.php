<?php

namespace DataKit\DataViews\Data;

use DataKit\DataViews\Data\Exception\DataNotFoundException;

/**
 * Represents a mutable data source.
 *
 * @since $ver$
 */
interface MutableDataSource extends DataSource {
	/**
	 * Returns whether the data source can delete results.
	 *
	 * @since $ver$
	 *
	 * @return bool
	 */
	public function can_delete(): bool;

	/**
	 * Deletes all data by their ID.
	 *
	 * @param string ...$ids The IDs of the data sets to delete.
	 *
	 * @throws DataNotFoundException When the data was not found.
	 */
	public function delete_data_by_id( string ...$ids ): void;
}
