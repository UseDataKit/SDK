<?php

namespace DataKit\DataView;

use DataKit\DataView\DataView\DataView;
use DataKit\DataView\DataView\DataViewRepository;
use DataKit\DataView\Rest\Router;

final class DataViewPlugin {
	/**
	 * The singleton plugin instance.
	 * @since $ver$
	 * @var self
	 */
	private static self $instance;

	/**
	 * The DataView Repository.
	 * @since $ver$
	 * @var DataViewRepository
	 */
	private DataViewRepository $data_view_repository;

	/**
	 * Creates the plugin.
	 * @since $ver$
	 *
	 * @param DataViewRepository $data_view_repository The DataView repository.
	 */
	private function __construct( DataViewRepository $data_view_repository ) {
		$this->data_view_repository = $data_view_repository;

		Router::get_instance( $this->data_view_repository );

		add_action( 'data-view/register-data-view', [ $this, 'register_data_view' ] );
	}

	/**
	 * Registers a DataView on the repository.
	 * @since $ver$
	 *
	 * @param DataView $data_view The DataView.
	 */
	public function register_data_view( DataView $data_view ) : void {
		$this->data_view_repository->save( $data_view );
	}

	/**
	 * Return and maybe initialize the singleton router.
	 * @since $ver$
	 * @return self The router.
	 */
	public static function get_instance( DataViewRepository $repository ) : self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( $repository );
		}

		return self::$instance;
	}
}
