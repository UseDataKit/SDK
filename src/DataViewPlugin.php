<?php

namespace DataKit\DataViews;

use DataKit\DataViews\Component\Shortcode;
use DataKit\DataViews\DataView\DataView;
use DataKit\DataViews\DataView\DataViewRepository;
use DataKit\DataViews\Rest\Router;

/**
 * Entry point for the plugin.
 * @since $ver$
 */
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
		Shortcode::get_instance( $this->data_view_repository );

		add_action( 'datakit/dataview/register', [ $this, 'register_data_view' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_scripts' ] );
	}

	/**
	 * Registers a DataView on the repository.
	 * @since $ver$
	 *
	 * @param DataView $data_view The DataView.
	 */
	public function register_data_view( DataView $data_view ): void {
		$this->data_view_repository->save( $data_view );
	}

	/**
	 * Register the scripts and styles.
	 * @since $ver$
	 */
	public function register_scripts(): void {
		$assets_dir = plugin_dir_url( DATAVIEW_PLUGIN_PATH );

		wp_register_script( 'datakit/dataview', $assets_dir . 'assets/js/dataview.js', [], null, true );
		wp_register_style( 'datakit/dataview', $assets_dir . 'assets/css/dataview.css' );
		wp_add_inline_script(
			'datakit/dataview',
			implode( "\n", [
				'let datakit_dataviews = {};',
				sprintf( 'const datakit_dataviews_rest_endpoint = "%s";', esc_attr( get_rest_url( null, Router::NAMESPACE ) ) ),
			] ),
			'before' );
	}

	/**
	 * Return and maybe initialize the singleton plugin.
	 * @since $ver$
	 * @return self The plugin.
	 */
	public static function get_instance( DataViewRepository $repository ): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( $repository );
		}

		return self::$instance;
	}
}
