<?php

namespace DataKit\DataViews\DataView;

/**
 * Represents the storage for all registered DataViews.
 *
 * @since $ver$
 */
interface DataViewRepository {
	/**
	 * Returns an array of DataViews, keyed by their ID.
	 *
	 * @since $ver$
	 *
	 * @return array<string, DataView> The DataViews.
	 */
	public function all(): array;

	/**
	 * Returns a DataView by its ID.
	 *
	 * @param string $id The DataView ID.
	 *
	 * @throws DataViewNotFoundException When the DataView could not be found.
	 *
	 * @return DataView The DataView.
	 */
	public function get( string $id ): DataView;

	/**
	 * Returns whether a DataView is registered in the repository by the provided ID.
	 *
	 * @since $ver$
	 *
	 * @param string $id The ID.
	 *
	 * @return bool Whether a DataView is registered by the ID.
	 */
	public function has( string $id ): bool;

	/**
	 * Saves the DataView on the repository.
	 *
	 * @since $ver$
	 *
	 * @param DataView $data_view The DataView.
	 *
	 * @return void
	 */
	public function save( DataView $data_view ): void;

	/**
	 * Removes the DataView from the repository.
	 *
	 * @since $ver$
	 *
	 * @param DataView $data_view The DataView.
	 *
	 * @return void
	 */
	public function delete( DataView $data_view ): void;
}
