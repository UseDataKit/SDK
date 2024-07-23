<?php

namespace DataKit\DataViews\DataView;

/**
 * A DataView repository backed by an array.
 *
 * @since $ver$
 */
final class ArrayDataViewRepository implements DataViewRepository {
	/**
	 * Contains the registered DataViews.
	 *
	 * @since $ver$
	 *
	 * @var array<string, DataView>
	 */
	private array $data_views = [];

	/**
	 * Creates the repository.
	 *
	 * @since $ver$
	 *
	 * @param DataView[] $data_views The DataViews.
	 */
	public function __construct( array $data_views = [] ) {
		foreach ( $data_views as $data_view ) {
			$this->save( $data_view );
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function all(): array {
		return $this->data_views;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get( string $id ): DataView {
		$data_view = $this->data_views[ $id ] ?? null;

		if ( ! $data_view ) {
			throw new DataViewNotFoundException();
		}

		return $data_view;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function save( DataView $data_view ): void {
		$this->data_views[ $data_view->id() ] = $data_view;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function delete( DataView $data_view ): void {
		unset( $this->data_views[ $data_view->id() ] );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function has( string $id ): bool {
		return isset( $this->data_views[ $id ] );
	}
}
