<?php

namespace DataKit\DataViews\Data;

use Countable;
use DataKit\DataViews\Data\Exception\DataNotFoundException;
use DataKit\DataViews\Data\Exception\DataSourceNotFoundException;
use DataKit\DataViews\Data\Exception\DataSourceException;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Search;
use DataKit\DataViews\DataView\Sort;

/**
 * Represents a read-only data source.
 *
 * Developer notes:
 * - Data source should throw a {@see DataSourceNotFoundException} when creation of the data source fails.
 * - The data source should preferably be immutable, as {@see BaseDataSource} helper methods do.
 *
 * @since $ver$
 */
interface DataSource extends Countable {
	/**
	 * The unique ID of the data source.
	 *
	 * @since $ver$
	 *
	 * @return string
	 */
	public function id(): string;

	/**
	 * Returns IDs for the data source.
	 *
	 * @since $ver$
	 *
	 * @param int $limit  The limit.
	 * @param int $offset The offset.
	 *
	 * @return string[] The id's.
	 * @throws DataSourceException When the data could not be retrieved.
	 */
	public function get_data_ids( int $limit = 20, int $offset = 0 ): array;

	/**
	 * Returns data for the provided ID.
	 *
	 * @since $ver$
	 *
	 * @param string $id The ID.
	 *
	 * @return array The provided data.
	 * @throws DataSourceException When the data could not be retrieved.
	 * @throws DataNotFoundException When the data was not found.
	 */
	public function get_data_by_id( string $id ): array;

	/**
	 * Returns a value=>label array of the available fields for the data source.
	 *
	 * @since $ver$
	 *
	 * @return array<string, string> The fields.
	 */
	public function get_fields(): array;

	/**
	 * Returns the total amount of results for this data source.
	 *
	 * This method should take into account any filtering that might be applied.
	 *
	 * @since $ver$
	 *
	 * @return int The total amount of results.
	 * @throws DataSourceException When the data could not be retrieved.
	 */
	public function count(): int;

	/**
	 * Sets the filters for the data source.
	 *
	 * @since $ver$
	 *
	 * @param null|Filters $filters The filter.
	 *
	 * @return DataSource|static The data source.
	 */
	public function filter_by( ?Filters $filters );

	/**
	 * Filters entries based on a search string.
	 *
	 * @since $ver$
	 *
	 * @param Search|null $search The search query.
	 *
	 * @return DataSource|static The data source.
	 */
	public function search_by( ?Search $search );

	/**
	 * Sets the filters for the data source.
	 *
	 * @since $ver$
	 *
	 * @param Sort|null $sort The sorting.
	 *
	 * @return DataSource|static The data source.
	 */
	public function sort_by( ?Sort $sort );
}
