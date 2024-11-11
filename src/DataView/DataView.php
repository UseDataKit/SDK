<?php

namespace DataKit\DataViews\DataView;

use DataKit\DataViews\AccessControl\AccessControlManager;
use DataKit\DataViews\AccessControl\Capability\ViewField;
use DataKit\DataViews\Data\DataSource;
use DataKit\DataViews\Data\Exception\DataSourceException;
use DataKit\DataViews\Data\MutableDataSource;
use DataKit\DataViews\DataViewException;
use DataKit\DataViews\Field\Field;
use InvalidArgumentException;
use JsonException;

/**
 * Represents a single DataView entity.
 *
 * @since $ver$
 */
final class DataView {
	/**
	 * The DataView ID.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * The view types.
	 *
	 * @since $ver$
	 *
	 * @var View[]
	 */
	private array $views;

	/**
	 * The fields to show on the DataView.
	 *
	 * @since $ver$
	 *
	 * @var Field[]
	 */
	private array $directory_fields = [];

	/**
	 * The fields to show on a single result.
	 *
	 * @since $ver$
	 * @var Field[]
	 */
	public array $view_fields = [];

	/**
	 * The data source that feeds the view.
	 *
	 * @since $ver$
	 * @var DataSource
	 */
	private DataSource $data_source;

	/**
	 * The sorting order.
	 *
	 * @since $ver$
	 *
	 * @var Sort|null
	 */
	private ?Sort $sort;

	/**
	 * The provided filters.
	 *
	 * @since $ver$
	 *
	 * @var Filters|null
	 */
	private ?Filters $filters;

	/**
	 * The provided actions.
	 *
	 * @since $ver$
	 *
	 * @var Actions|null
	 */
	private ?Actions $actions;

	/**
	 * The applied search query.
	 *
	 * @since $ver$
	 *
	 * @var Search|null
	 */
	private ?Search $search = null;

	/**
	 * The pagination info.
	 *
	 * @since $ver$
	 *
	 * @var Pagination
	 */
	private Pagination $pagination;

	/**
	 * The field used as a media field.
	 *
	 * @since $ver$
	 *
	 * @var Field|null
	 */
	private ?Field $media_field = null;

	/**
	 * The field used as the primary field.
	 *
	 * @since $ver$
	 *
	 * @var Field|null
	 */
	private ?Field $primary_field = null;

	/**
	 * Whether the DataView supports searching.
	 *
	 * @since $ver$
	 *
	 * @var bool
	 */
	private bool $has_search = true;

	/**
	 * Creates the DataView.
	 *
	 * @since $ver$
	 *
	 * @param View         $view        The View types.
	 * @param string       $id          The DataView ID.
	 * @param array        $fields      The fields.
	 * @param DataSource   $data_source The data source.
	 * @param Sort|null    $sort        The sorting.
	 * @param Filters|null $filters     The filters.
	 * @param Actions|null $actions     The actions.
	 */
	private function __construct(
		View $view,
		string $id,
		array $fields,
		DataSource $data_source,
		?Sort $sort = null,
		?Filters $filters = null,
		?Actions $actions = null
	) {
		$this->id    = $id;
		$this->views = [ $view ];

		$this->filters( $filters );
		$this->actions( $actions );

		$this->sort        = $sort;
		$this->data_source = $data_source;
		$this->pagination  = Pagination::default();

		$this->ensure_valid_fields( ...$fields );
	}

	/**
	 * Creates the DataView of the table type.
	 *
	 * @since $ver$
	 *
	 * @param string       $id          The DataView ID.
	 * @param DataSource   $data_source The data source.
	 * @param array        $fields      The fields.
	 * @param Sort|null    $sort        The sorting.
	 * @param Filters|null $filters     The filters.
	 * @param Actions|null $actions     The actions.
	 */
	public static function table(
		string $id,
		DataSource $data_source,
		array $fields,
		?Sort $sort = null,
		?Filters $filters = null,
		?Actions $actions = null
	): self {
		return new self(
			View::Table(),
			$id,
			$fields,
			$data_source,
			$sort,
			$filters,
			$actions,
		);
	}

	/**
	 * Creates the DataView of the grid type.
	 *
	 * @since $ver$
	 *
	 * @param string       $id          The DataView ID.
	 * @param DataSource   $data_source The data source.
	 * @param array        $fields      The fields.
	 * @param Sort|null    $sort        The sorting.
	 * @param Filters|null $filters     The filters.
	 * @param Actions|null $actions     The actions.
	 */
	public static function grid(
		string $id,
		DataSource $data_source,
		array $fields,
		?Sort $sort = null,
		?Filters $filters = null,
		?Actions $actions = null
	): self {
		return new self(
			View::Grid(),
			$id,
			$fields,
			$data_source,
			$sort,
			$filters,
			$actions,
		);
	}

	/**
	 * Creates the DataView of the list type.
	 *
	 * @since $ver$
	 *
	 * @param string       $id          The DataView ID.
	 * @param DataSource   $data_source The data source.
	 * @param array        $fields      The fields.
	 * @param Sort|null    $sort        The sorting.
	 * @param Filters|null $filters     The filters.
	 * @param Actions|null $actions     The actions.
	 */
	public static function list(
		string $id,
		DataSource $data_source,
		array $fields,
		?Sort $sort = null,
		?Filters $filters = null,
		?Actions $actions = null
	): self {
		return new self(
			View::List(),
			$id,
			$fields,
			$data_source,
			$sort,
			$filters,
			$actions,
		);
	}

	/**
	 * Adds additional view types to support.
	 *
	 * Note: The first view will always be kept as the default.
	 *
	 * @since $ver$
	 *
	 * @param View ...$views The view types.
	 *
	 * @return self The DataView.
	 */
	public function supports( View ...$views ): self {
		$this->views = array_unique( [ $this->views[0], ...$views ] );

		return $this;
	}

	/**
	 * Makes sure the fields are of the correct type.
	 *
	 * @since $ver$
	 *
	 * @param Field ...$fields The fields.
	 **/
	private function ensure_valid_fields( Field ...$fields ): void {
		$this->directory_fields = array_merge( $this->directory_fields, $fields );
	}

	/**
	 * Returns the ID of the DataView.
	 *
	 * @since $ver$
	 * @return string The ID.
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Returns the view data object.
	 *
	 * @since $ver$
	 * @return array The view data object.
	 */
	private function view(): array {
		return [
			'search'  => (string) $this->search,
			'type'    => (string) $this->views[0],
			'filters' => $this->filters ? $this->filters->to_array() : [],
			'perPage' => $this->pagination->limit(),
			'page'    => $this->pagination->page(),
			'sort'    => $this->sort ? $this->sort->to_array() : [],
			'fields'  => $this->get_field_ids( fn( Field $field ): bool => ! $field->is_hidden() ),
			'layout'  => $this->layout( $this->views[0] ),
		];
	}

	/**
	 * Returns a data source with sorting and filters applied.
	 *
	 * @since $ver$
	 *
	 * @return DataSource The data source.
	 */
	public function data_source(): DataSource {
		return $this->data_source
			->sort_by( $this->sort )
			->filter_by( $this->filters )
			->search_by( $this->search );
	}

	/**
	 * Returns the data object for DataView.
	 *
	 * @since $ver$
	 *
	 * @param DataSource|null $data_source Data source to use.
	 * @param Pagination|null $pagination  Pagination settings.
	 *
	 * @return array The data object.
	 * @throws DataSourceException When the data source encounters an issue.
	 */
	public function get_data( ?DataSource $data_source = null, ?Pagination $pagination = null ): array {
		$data_source ??= $this->data_source();
		$pagination  ??= $this->pagination;

		$object = [];

		foreach ( $data_source->get_data_ids( $pagination->limit(), $pagination->offset() ) as $data_id ) {
			/**
			 * // Todo: this is a possible breach of security as all data is passed along in the JS.
			 * But the merge tags (on JS side) need access to the raw data, so the field needs to tell us
			 * which field is need, and only disclose those values.
			 */
			$data = $data_source->get_data_by_id( $data_id );

			foreach ( $this->allowed_fields( $this->directory_fields ) as $field ) {
				$data[ $field->uuid() ] = $field->get_value( $data );
			}

			$object[] = $data;
		}

		return $object;
	}

	/**
	 * Returns a data item for a single result.
	 *
	 * This value object contains (a reference to) the fields as well as the data. Used by the single entry template.
	 *
	 * @since $ver$
	 *
	 * @param string $data_id The data item ID.
	 *
	 * @return DataItem The data item.
	 * @throws DataSourceException When the data source encounters an issue.
	 */
	public function get_view_data_item( string $data_id ): DataItem {
		$data   = $this->data_source()->get_data_by_id( $data_id );
		$fields = $this->allowed_fields( $this->view_fields );

		foreach ( $fields as $field ) {
			$data[ $field->uuid() ] = $field->get_value( $data );
		}

		return DataItem::from_array(
			[
				'fields' => $fields,
				'data'   => $data,
			],
		);
	}

	/**
	 * Returns all the fields for the directory view.
	 *
	 * @since $ver$
	 *
	 * @return array[] The fields as arrays.
	 */
	private function directory_fields_for_json(): array {
		$fields = [];

		foreach ( $this->allowed_fields( $this->directory_fields ) as $field ) {
			$fields[] = array_filter(
				$field->to_array(),
				static fn( $value ) => ! is_null( $value ),
			);
		}

		return $fields;
	}

	/**
	 * Returns the `defaultLayouts` object.
	 *
	 * @since $ver$
	 *
	 * @return array<string,array{layout:array}> The supported layouts.
	 */
	private function default_layouts(): array {
		if ( count( $this->views ) < 2 ) {
			return [];
		}

		$layouts = [];
		foreach ( $this->views as $view ) {
			$layouts[ (string) $view ]['layout'] = $this->layout( $view );
		}

		return $layouts;
	}

	/**
	 * Returns the field keys of (a filtered set of) the fields.
	 *
	 * @since    $ver$
	 *
	 * @formatter:off
	 *
	 * @phpcs:ignore Squiz.Commenting.FunctionComment.ParamNameNoMatch
	 * @param null|callable(Field $field):bool $filter A callback to filter fields.
	 *
	 * @formatter:on
	 *
	 * @return string[] The field IDs.
	 */
	private function get_field_ids( ?callable $filter = null ): array {
		$field_ids = [];

		foreach ( $this->allowed_fields( $this->directory_fields ) as $field ) {
			if ( $filter && ! $filter( $field ) ) {
				continue;
			}

			$field_ids[] = $field->uuid();
		}

		return $field_ids;
	}

	/**
	 * Returns an instance of the DataView with a particular pagination.
	 *
	 * @since $ver$
	 *
	 * @param int $per_page The results per page.
	 * @param int $page     The current page.
	 *
	 * @return self The DataView.
	 */
	public function paginate( int $per_page, int $page = 1 ): self {
		$this->pagination = new Pagination( $page, $per_page );

		return $this;
	}

	/**
	 * Returns an instance of the DataView with search enabled, and a provided search string.
	 *
	 * @since $ver$
	 *
	 * @param string|Search $search The query to search.
	 *
	 * @return self The DataView.
	 */
	public function search( $search = '' ): self {
		if ( is_string( $search ) ) {
			$search = Search::from_string( $search );
		}

		if ( ! $search instanceof Search ) {
			throw new \InvalidArgumentException( 'Search value must either be a string or Search object.' );
		}

		$this->has_search = true;
		$this->search     = $search;

		return $this;
	}

	/**
	 * Returns an instance of the DataView with searching disabled.
	 *
	 * @since $ver$
	 *
	 * @return self The DataView instance.
	 */
	public function disable_search(): self {
		$this->has_search = false;
		$this->search     = null;

		return $this;
	}

	/**
	 * Returns an instance of the DataView with a particular sorting applied.
	 *
	 * @since $ver$
	 *
	 * @param Sort|null $sort The sort object.
	 *
	 * @return self The DataView.
	 */
	public function sort( ?Sort $sort ): self {
		$this->sort = $sort;

		return $this;
	}

	/**
	 * Returns the data needed to set up a WordPress DataViews component.
	 *
	 * @since $ver$
	 *
	 * @return array The data for a WordPress DataViews component.
	 * @throws DataSourceException When the data source encounters an issue.
	 */
	public function to_array(): array {
		return [
			'search'         => $this->has_search,
			'defaultLayouts' => $this->default_layouts(),
			'paginationInfo' => $this->pagination->info( $this->data_source() ),
			'view'           => $this->view(),
			'fields'         => $this->directory_fields_for_json(),
			'data'           => $this->get_data(),
			'actions'        => array_values( $this->actions ? $this->actions->to_array() : [] ),
		];
	}

	/**
	 * Returns the JavaScript object for a WordPress DataViews component.
	 *
	 * Note: removing "__RAW__ and __ENDRAW__" ensure certain code is provided as JavaScript, instead of a string.
	 *
	 * @since $ver$
	 *
	 * @return string The JavaScript object.
	 * @throws DataViewException If there was an issue rendering the JSON blob.
	 */
	public function to_js( bool $is_pretty = false ): string {
		$flags = JSON_THROW_ON_ERROR;
		if ( $is_pretty ) {
			$flags |= JSON_PRETTY_PRINT;
		}

		try {
			return preg_replace_callback(
				'/\"__RAW__(.*?)__ENDRAW__\"/s',
				static fn( array $matches ): string => stripslashes( $matches[1] ),
				json_encode( $this->to_array(), $flags ),
			);
		} catch ( JsonException $e ) {
			throw new DataViewException( $e->getMessage(), $e->getCode(), $e );
		}
	}

	/**
	 * Makes a single result of a DataView visible within a modal.
	 *
	 * Note: This method prepends a primary action to open a single entry template in a modal.
	 *
	 * @since $ver$
	 *
	 * @param array         $fields   The fields to show.
	 * @param string        $label    The label to call the action.
	 * @param callable|null $callback Callback that receives the action as the single argument to perform changes on.
	 *
	 * @return self The DataView with the view action.
	 */
	public function viewable( array $fields, string $label = 'View', ?callable $callback = null ): self {
		$this->add_view_fields( ...$fields );

		$view_rest_url = sprintf( '{REST_ENDPOINT}/views/%s/data/{id}', $this->id() );

		$view_action = Action::modal( 'view', $label, $view_rest_url, true )
			->primary( 'info' );

		if ( $callback ) {
			$view_action = $callback( $view_action );
			if ( ! $view_action instanceof Action ) {
				throw new InvalidArgumentException( 'The provided callback should return an Action object.' );
			}
		}

		$this->actions = $this->actions
			? $this->actions->prepend( $view_action )
			: Actions::of( $view_action );

		return $this;
	}

	/**
	 * Returns an instance of the DataView which includes a delete action.
	 *
	 * Note: The method adds a primary destructive action, with a confirmation. To change the action, you
	 * can provide a callback which receives (and should return) the action.
	 *
	 * @since $ver$
	 *
	 * @param string        $label    The label to use on the button.
	 * @param callable|null $callback Callback that receives the action as the single argument to perform changes on.
	 *
	 * @return self The DataView with a delete action.
	 */
	public function deletable( string $label = 'Delete', ?callable $callback = null ): self {
		if (
			! $this->data_source instanceof MutableDataSource
			|| ! $this->data_source->can_delete()
		) {
			return $this;
		}

		$delete_rest_url = sprintf( '{REST_ENDPOINT}/views/%s/data', $this->id() );

		$delete_action = Action::ajax( 'delete', $label, $delete_rest_url, 'DELETE', [ 'id' => '{id}' ], true )
			->destructive()
			->bulk()
			->primary( 'trash' )
			->confirm( esc_html__( 'Are you sure you want to delete these items?', 'dk-datakit' ) );

		if ( $callback ) {
			$delete_action = $callback( $delete_action );
			if ( ! $delete_action instanceof Action ) {
				throw new InvalidArgumentException( 'The provided callback should return an Action object.' );
			}
		}

		$this->actions = $this->actions
			? $this->actions->append( $delete_action )
			: Actions::of( $delete_action );

		return $this;
	}

	/**
	 * Adds fields for the single entry view.
	 *
	 * @since $ver$
	 *
	 * @param Field ...$fields The fields.
	 */
	private function add_view_fields( Field ...$fields ): void {
		$this->view_fields = $fields;
	}

	/**
	 * Returns the values needed for the `layout` key of a DataViews view type.
	 *
	 * @since $ver$
	 *
	 * @param View $view The view.
	 *
	 * @return array<string,mixed> The layout properties for the view.
	 */
	private function layout( View $view ): array {
		$output = [];

		if ( $this->primary_field ) {
			$output['primaryField'] = $this->primary_field->uuid();
		}

		if ( $view->equals( View::Grid() ) ) {
			$output['badgeFields']  = $this->get_field_ids( static fn( Field $field ): bool => $field->is_badge() );
			$output['columnFields'] = $this->get_field_ids( static fn( Field $field ): bool => $field->is_column() );
		}

		if ( ! $view->equals( View::Table() ) ) {
			$output['mediaField'] = $this->get_media_field_id();
		}

		return array_filter( $output );
	}

	/**
	 * Returns an instance of the DataView with a primary field.
	 *
	 * @since $ver$
	 *
	 * @param Field|null $field The primary field.
	 *
	 * @return self The DataView.
	 */
	public function primary_field( ?Field $field ): self {
		$this->primary_field = $field;

		return $this;
	}

	/**
	 * Returns an instance of the DataView with a media field.
	 *
	 * @since $ver$
	 *
	 * @param Field|null $field The media field.
	 *
	 * @return self The DataView.
	 */
	public function media_field( ?Field $field ): self {
		$this->media_field = $field && $field->is_media_field() ? $field : null;

		return $this;
	}

	/**
	 * Returns the id of the media field.
	 *
	 * If no media field is provided, it will assume the first media field it comes across.
	 *
	 * @return string The media field id.
	 */
	private function get_media_field_id(): string {
		if ( $this->media_field ) {
			return $this->media_field->uuid();
		}

		$image_fields = $this->get_field_ids(
			static fn( Field $field ): bool => $field->is_media_field(),
		);

		return $image_fields ? reset( $image_fields ) : '';
	}

	/**
	 * Filters out the fields the current user cannot view.
	 *
	 * @since $ver$
	 *
	 * @return Field[] The fields.
	 */
	private function allowed_fields( array $fields ): array {
		return array_filter(
			$fields,
			fn( Field $field ) => AccessControlManager::current()->can(
				new ViewField( $this, $field )
			)
		);
	}

	/**
	 * Sets the filters for this view.
	 *
	 * @since $ver$
	 *
	 * @param Filters|null $filters The filters.
	 */
	public function filters( ?Filters $filters ): self {
		$this->filters = $filters;

		return $this;
	}

	/**
	 * Sets the actions for this view.
	 *
	 * @since $ver$
	 *
	 * @param Actions|callable|null $actions The actions.
	 */
	public function actions( $actions ): self {
		if ( is_callable( $actions ) ) {
			$actions = $actions( $this->actions ); // Provides the old actions.
		}

		if ( null === $actions || $actions instanceof Actions ) {
			$this->actions = $actions;
		}

		return $this;
	}
}
