<?php

namespace DataKit\DataView\Rest;

use DataKit\DataView\DataView\DataViewNotFoundException;
use DataKit\DataView\DataView\DataViewRepository;
use DataKit\DataView\DataView\Filters;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Router responsible for registering and routing the REST routes.
 * @since $ver$
 */
final class Router {
	/**
	 * The API namespace.
	 * @since $ver$
	 * @var string
	 */
	private const NAMESPACE = 'data-view/v1';

	/**
	 * The singleton router instance.
	 * @since $ver$
	 * @var Router
	 */
	private static self $instance;

	/**
	 * The DataViews repository.
	 * @since $ver$
	 * @var DataViewRepository
	 */
	private DataViewRepository $data_view_repository;

	/**
	 * Creates the router.
	 * @since $ver$
	 */
	private function __construct( DataViewRepository $data_view_repository ) {
		$this->data_view_repository = $data_view_repository;

		add_filter( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registers the REST endpoints.
	 * @since $ver$
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/' . 'view/(?<id>[^/]+)', [
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_view' ],
				'permission_callback' => [ $this, 'get_view_permissions_check' ],
				'args'                => [
					'search'  => [
						'default'           => '',
						'sanitize_callback' => fn( $value ): string => (string) $value,
					],
					'filters' => [
						'default'           => [],
						'validate_callback' => fn( $value ) => is_array( $value ),
					],
					'page'    => [
						'default'           => 1,
						'validate_callback' => fn( $value ) => is_numeric( $value ) && $value > 0,
					],
					'perPage' => [
						'default'           => 100,
						'validate_callback' => fn( $value ) => is_int( $value ) && $value > 0,
					],
					'sort'    => [
						'default'           => [],
						'validate_callback' => fn( $value ) => is_array( $value ),
					],
				],
			],
		] );
	}

	/**
	 * Returns whether the current user can retrieve the view content.
	 * @since $ver$
	 * @return bool
	 */
	public function get_view_permissions_check(): bool {
		//todo
		return true;
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_Error
	 */
	public function get_view( WP_REST_Request $request ) {
		try {
			$data_view = $this->data_view_repository->get( $request->get_param( 'id' ) );
			$params    = $request->get_params();

			// Update view with provided params.
			$data_view = $data_view
				->with_filters(Filters::from_array( $params['filters'] ?? [] ) )
				->with_search( $params['search'] ?? '' );

			if ( $params['page'] ?? 0 ) {
				$data_view = $data_view->with_pagination( $params['page'], $params['per_page'] ?? null );
			}

			return $data_view->to_array();
		} catch ( \Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Return and maybe initialize the singleton router.
	 * @since $ver$
	 * @return self The router.
	 */
	public static function get_instance( DataViewRepository $repository ): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( $repository );
		}

		return self::$instance;
	}
}
