<?php

namespace DataKit\DataViews\Rest;

use DataKit\DataViews\Data\MutableDataSource;
use DataKit\DataViews\DataView\DataViewRepository;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Pagination;
use DataKit\DataViews\DataView\Sort;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Router responsible for registering and routing the REST routes.
 *
 * @since $ver$
 */
final class Router {
	/**
	 * The API namespace.
	 *
	 * @since $ver$
	 * @var string
	 */
	public const NAMESPACE = 'dataviews/v1';

	/**
	 * The singleton router instance.
	 *
	 * @since $ver$
	 * @var Router
	 */
	private static self $instance;

	/**
	 * The DataViews repository.
	 *
	 * @since $ver$
	 * @var DataViewRepository
	 */
	private DataViewRepository $data_view_repository;

	/**
	 * The view controller.
	 *
	 * @since $ver$
	 * @var ViewController
	 */
	private ViewController $view_controller;

	/**
	 * Creates the router.
	 *
	 * @since $ver$
	 */
	private function __construct( DataViewRepository $data_view_repository ) {
		$this->data_view_repository = $data_view_repository;
		$this->view_controller      = new ViewController( $data_view_repository );

		// @phpstan-ignore return.missing
		add_filter( 'rest_api_init', [ $this, 'register_routes' ] );
	}


	/**
	 * Returns a prefixed url.
	 *
	 * @since $ver$
	 *
	 * @param string $url The url to prefix.
	 *
	 * @return string The full url.
	 */
	public static function get_url( string $url ): string {
		return rest_url( self::NAMESPACE . '/' . trim( $url, '/' ) );
	}

	/**
	 * Registers the REST endpoints.
	 *
	 * @since $ver$
	 */
	public function register_routes(): void {
		register_rest_route(
            self::NAMESPACE,
            '/views/(?<id>[^/]+)$',
            [
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
			]
        );

		register_rest_route(
            self::NAMESPACE,
            '/views/(?<view_id>[^/]+)/data/(?<data_id>[^/]+)',
            [
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this->view_controller, 'get_item' ],
					'permission_callback' => [ $this->view_controller, 'can_view' ],
				],
			]
        );

		register_rest_route(
            self::NAMESPACE,
            '/views/(?<view_id>[^/]+)/data',
            [
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_view_data' ],
					'permission_callback' => [ $this, 'delete_view_data_permissions_check' ],
				],
			]
        );
	}

	/**
	 * Returns whether the current user can retrieve the view content.
	 *
	 * @since $ver$
	 * @return bool
	 */
	public function get_view_permissions_check(): bool {
		// todo: Add permissions checks.
		return true;
	}

	/**
	 * Returns whether the current user can delete view data the view content.
	 *
	 * @since $ver$
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool
	 */
	public function delete_view_data_permissions_check( WP_REST_Request $request ): bool {
		try {
			$data_view   = $this->data_view_repository->get( $request->get_param( 'view_id' ) ?? '' );
			$data_source = $data_view->data_source();

			if (
				! $data_source instanceof MutableDataSource
				|| ! $data_source->can_delete()
			) {
				return false;
			}

			// todo: add test for user permissions.
			return true;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Returns the data for a DataView.
	 *
	 * @since $ver$
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return array|WP_Error The data or error object.
	 */
	public function get_view( WP_REST_Request $request ) {
		try {
			$data_view = $this->data_view_repository->get( $request->get_param( 'id' ) );
			$params    = $request->get_params();

			// Update view with provided params.
			$data_source = $data_view->data_source()
				->filter_by( Filters::from_array( $params['filters'] ?? [] ) )
				->search_by( $params['search'] ?? '' );

			$pagination = ( $params['page'] ?? null ) ? Pagination::from_array( $params ) : Pagination::default();

			if ( $params['sort'] ?? [] ) {
				$data_source = $data_source->sort_by( Sort::from_array( $params['sort'] ) );
			}

			return [
				'data'           => $data_view->get_data( $data_source, $pagination ),
				'paginationInfo' => $pagination->info( $data_source ),
			];
		} catch ( \Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Deletes a dataset on a dataview.
	 *
	 * @since $ver$
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return array|WP_Error
	 */
	public function delete_view_data( WP_REST_Request $request ) {
		try {
			$data_view   = $this->data_view_repository->get( $request->get_param( 'view_id' ) ?? '' );
			$data_source = $data_view->data_source();

			if (
				! $data_source instanceof MutableDataSource
				|| ! $data_source->can_delete()
			) {
				return [];
			}

			$data_ids = $request->get_param( 'id' ) ?? [];
			$data_source->delete_data_by_id( ...$data_ids );

			return [ 'id' => $data_ids ];
		} catch ( \Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage() );
		}
	}

	/**
	 * Return and maybe initialize the singleton router.
	 *
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
