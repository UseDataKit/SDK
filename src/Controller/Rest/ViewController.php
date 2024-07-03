<?php

namespace DataKit\DataViews\Controller\Rest;

use DataKit\DataViews\DataView\DataViewRepository;
use WP_REST_Request;

/**
 * Controller responsible for a single view result.
 * @since $ver$
 */
final class ViewController {
	/**
	 * The dataview repository.
	 * @since $ver$
	 * @var DataViewRepository
	 */
	private DataViewRepository $dataview_repository;

	/**
	 * Creates the controller.
	 * @since $ver$
	 *
	 * @param DataViewRepository $dataview_repository The dataview repository
	 */
	public function __construct( DataViewRepository $dataview_repository ) {
		$this->dataview_repository = $dataview_repository;
	}

	/**
	 * Whether the current user can view the result.
	 * @since $ver$
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool Whether the current user can view the result.
	 */
	public function can_view( WP_REST_Request $request ): bool {
		return true;
	}

	/**
	 * Returns the result for a single item.
	 * @since $ver$
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return array The response..
	 */
	public function get_item( WP_REST_Request $request ): array {
		$view_id = (string) ( $request->get_param( 'view_id' ) ?? '' );
		$data_id = (string) ( $request->get_param( 'data_id' ) ?? '' );

		$dataview = $this->dataview_repository->get( $view_id );
		$data     = $dataview->data_source()->get_data_by_id( $data_id );

		foreach ( $dataview->view_fields as $field ) {
			$data[ $field->uuid() ] = $field->get_value( $data );
		}

		ob_start();

		$template = dirname( DATAVIEW_PLUGIN_PATH ) . '/templates/view/table.php';

		( static function ( array $fields, array $data ) use ( $template ) {
			require $template;
		} )( $dataview->view_fields, $data );

		$html = ob_get_clean();

		return [
			'dataview'  => $dataview->id(),
			'result_id' => $data_id,
			'html'      => $html,
		];
	}
}
