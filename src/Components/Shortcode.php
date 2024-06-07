<?php

namespace DataKit\DataView\Components;

use DataKit\DataView\DataView\DataViewRepository;

/**
 * Responsible for registering and rendering shortcodes.
 * @since $ver$
 */
final class Shortcode {
	/**
	 * The name of the shortcode.
	 * @since $ver$
	 * @var string
	 */
	private const SHORTCODE = 'dataview';

	/**
	 * The singleton plugin instance.
	 * @since $ver$
	 * @var self
	 */
	private static self $instance;
	/**
	 * The DataView repository.
	 * @since $ver$
	 * @var DataViewRepository
	 */
	private DataViewRepository $data_view_repository;

	/**
	 * Runtime cache for which dataviews are rendered.
	 * @since $ver$
	 * @var array
	 */
	private array $rendered = [];

	/**
	 * Creates the shortcode instance.
	 * @since $ver$
	 */
	private function __construct( DataViewRepository $data_view_repository ) {
		$this->data_view_repository = $data_view_repository;

		add_shortcode( self::SHORTCODE, [ $this, 'render_shortcode' ] );
	}


	/**
	 * Renders the short code.
	 * @since $ver$
	 *
	 * @param array $attributes The shortcode attributes.
	 *
	 * @return string The shortcode output.
	 */
	public function render_shortcode( array $attributes ): string {
		$id = $attributes['id'] ?? null;

		if (
			! $id
			|| ! $this->data_view_repository->has( $id )
		) {
			return '';
		}

		// Only add dataset once per id.
		if ( ! in_array( $id, $this->rendered, true ) ) {
			wp_enqueue_script( 'datakit/data-view' );
			wp_enqueue_style( 'datakit/data-view' );

			$dataview = $this->data_view_repository->get( $id );
			$js       = sprintf( 'datakit_dataviews["%s"] = %s;', esc_attr( $id ), json_encode( $dataview->to_array() ) );

			wp_add_inline_script( 'datakit/data-view', $js, 'before' );

			$this->rendered[] = $id;
		}

		return sprintf( '<div data-dataview="%s"></div>', $id );
	}

	/**
	 * Return and maybe initialize the singleton.
	 * @since $ver$
	 * @return self The singleton.
	 */
	public static function get_instance( DataViewRepository $data_view_repository ): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( $data_view_repository );
		}

		return self::$instance;
	}
}