<?php

namespace DataKit\DataViews\Data;

use Countable;
use DataKit\DataViews\Data\Exception\DataNotFoundException;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Sort;

/**
 * Represents a read-only data source.
 * @since $ver$
 */
interface DataSource extends Countable {
	/**
	 * The unique ID of the data source.
	 * @since $ver$
	 * @return string
	 */
	public function id() : string;

	/**
	 * The name of the data source visible in the UI.
	 * @since $ver$
	 * @return string
	 */
	public function name() : string;

	/**
	 * Returns the id's for the data source.
	 *
	 * @param int $limit The limit.
	 * @param int $offset The offset.
	 *
	 * @return string[] The id's.
	 */
	public function get_data_ids( int $limit = 20, int $offset = 0 ) : array;

	/**
	 * Returns the data for the provided id.
	 * @since $ver$
	 *
	 * @param string $id The id.
	 *
	 * @return array The provided data.
	 * @throws DataNotFoundException When the data was not found.
	 */
	public function get_data_by_id( string $id ) : array;

	/**
	 * Returns a value => label array of the available fields for the data source.
	 * @since $ver$
	 * @return array<string, string> The fields.
	 */
	public function get_fields() : array;

	/**
	 * Returns the total amount of results for this data source.
	 *
	 * This method should take into account any filtering that might be applied.
	 *
	 * @since $ver$
	 * @return int The total amount of results.
	 */
	public function count() : int;

	/**
	 * Sets the filters for the data source.
	 * @since $ver$
	 *
	 * @param null|Filters $filters The filter.
	 *
	 * @return static The (possibly immutable) data source.
	 */
	public function filter_by( ?Filters $filters );

	/**
	 * Filters entries based on a search string.
	 * @since $ver$
	 *
	 * @param string $search The search query.
	 *
	 * @return static The (possibly immutable) data source.
	 */
	public function search_by( string $search );

	/**
	 * Sets the filters for the data source.
	 * @since $ver$
	 *
	 * @param Sort|null $sort The sorting.
	 *
	 * @return static The (possibly immutable) data source.
	 */
	public function sort_by( ?Sort $sort );
}
