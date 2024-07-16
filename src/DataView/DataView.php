<?php

namespace DataKit\DataViews\DataView;

use DataKit\DataViews\Data\DataSource;
use DataKit\DataViews\Data\MutableDataSource;
use DataKit\DataViews\Field\Field;
use DataKit\DataViews\Rest\Router;
use JsonException;

/**
 * Represents a single DataView entity.
 *
 * @since $ver$
 */
final class DataView {
	/**
	 * The dataview ID.
	 *
	 * @since $ver$
	 * @var string
	 */
	private string $id;

	/**
	 * The primary view type.
	 *
	 * @since $ver$
	 * @var View
	 */
	private View $view;

	/**
	 * The fields to show on the DataView.
	 *
	 * @since $ver$
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
	 * @var Sort|null
	 */
	private ?Sort $sort;

	/**
	 * The provided filters.
	 *
	 * @since $ver$
	 * @var Filters|null
	 */
	private ?Filters $filters;

	/**
	 * The provided actions.
	 *
	 * @since $ver$
	 * @var Actions|null
	 */
	private ?Actions $actions;

	/**
	 * The applied search query.
	 *
	 * @since $ver$
	 * @var string
	 */
	private string $search = '';

	/**
	 * The pagination info.
	 *
	 * @since $ver$
	 * @var Pagination
	 */
	private Pagination $pagination;

	/**
	 * Whether the dataview supports searching.
	 *
	 * @since $ver$
	 * @var bool
	 */
	private bool $has_search = true;

	/**
	 * Creates the DataView.
	 *
	 * @since $ver$
	 *
	 * @param View         $view        The View type.
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
		$this->id   = $id;
		$this->view = $view;

		$this->filters     = $filters;
		$this->actions     = $actions;
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
	 * @param array        $fields      The fields.
	 * @param DataSource   $data_source The data source.
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
	) : self {
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
	 * Makes sure the fields are of the correct type.
	 *
	 * @since $ver$
	 *
	 * @param Field ...$fields The fields.
	 **/
	private function ensure_valid_fields( Field ...$fields ) : void {
		$this->directory_fields = array_merge( $this->directory_fields, $fields );
	}

	/**
	 * Returns the ID of the DataView.
	 *
	 * @since $ver$
	 * @return string The ID.
	 */
	public function id() : string {
		return $this->id;
	}

	/**
	 * Returns the view data object.
	 *
	 * @since $ver$
	 * @return array The view data object.
	 */
	private function view() : array {
		return [
			'search'       => $this->search,
			'type'         => (string) $this->view,
			'filters'      => $this->filters ? $this->filters->to_array() : [],
			'perPage'      => $this->pagination->limit(),
			'page'         => $this->pagination->page(),
			'sort'         => $this->sort ? $this->sort->to_array() : [],
			'hiddenFields' => $this->hidden_fields(),
			'layout'       => [],
		];
	}

	/**
	 * Returns a data source with sorting and filters applied.
	 *
	 * @since $ver$
	 * @return DataSource The data source.
	 */
	public function data_source() : DataSource {
		return $this->data_source
			->sort_by( $this->sort )
			->filter_by( $this->filters )
			->search_by( $this->search );
	}

	/**
	 * Returns the data object for Data View.
	 *
	 * @since $ver$
	 *
	 * @param DataSource|null $data_source Data source to use.
	 * @param Pagination|null $pagination  Pagination settings.
	 *
	 * @return array The data object.
	 */
	public function get_data( ?DataSource $data_source = null, ?Pagination $pagination = null ) : array {
		$data_source ??= $this->data_source();
		$pagination  ??= $this->pagination;

		$object = [];

		foreach ( $data_source->get_data_ids( $pagination->limit(), $pagination->offset() ) as $data_id ) {
			/**
			 * Todo: this is a possible breach of security as all data is passed along in the JS.
			 * But the merge tags need access to the raw data, so the field needs to tell us which field is need,
			 * and only disclose those values.
			 */
			$data = $data_source->get_data_by_id( $data_id );
			foreach ( $this->directory_fields as $field ) {
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
	 */
	public function get_view_data_item( string $data_id ) : DataItem {
		$data = $this->data_source()->get_data_by_id( $data_id );

		foreach ( $this->view_fields as $field ) {
			$data[ $field->uuid() ] = $field->get_value( $data );
		}

		return DataItem::from_array( [
			'fields' => $this->view_fields,
			'data'   => $data,
		] );
	}

	/**
	 * Returns all the fields for the dictionary view.
	 *
	 * @since $ver$
	 * @return array[] The fields as arrays.
	 */
	private function dictionary_fields() : array {
		$fields = [];

		foreach ( $this->directory_fields as $field ) {
			$fields[] = array_filter(
				$field->toArray(),
				static fn( $value ) => ! is_null( $value ),
			);
		}

		return $fields;
	}

	/**
	 * Returns the supportedLayouts object.
	 *
	 * @since $ver$
	 * @return string[] The supported layouts.
	 * @todo  provide option to add more.
	 */
	private function supported_layouts() : array {
		return [ (string) $this->view ];
	}

	/**
	 * Returns the field keys that should be hidden.
	 *
	 * @since $ver$
	 * @return string[] The field ID's.
	 */
	private function hidden_fields() : array {
		$hidden_fields = [];
		foreach ( $this->directory_fields as $field ) {
			if ( ! $field->is_hidden() ) {
				continue;
			}

			$hidden_fields[] = $field->id();
		}

		return $hidden_fields;
	}

	/**
	 * Returns an instance of the data view with a particular pagination.
	 *
	 * @since $ver$
	 *
	 * @param int      $per_page The results per page.
	 * @param int|null $page     The current page.
	 *
	 * @return self The dataview.
	 */
	public function paginate( int $per_page, int $page = 1 ) : self {
		$this->pagination = new Pagination( $page, $per_page );

		return $this;
	}

	/**
	 * Returns an instance of the data view with search enabled, and a provided search string.
	 *
	 * @since $ver$
	 *
	 * @param string $search The query to search.
	 *
	 * @return self The dataview.
	 */
	public function search( string $search = '' ) : self {
		$this->has_search = true;
		$this->search     = $search;

		return $this;
	}

	/**
	 * Returns an instance of the data view with searching disabled.
	 *
	 * @since $ver$
	 *
	 * @return self The data view instance.
	 */
	public function disable_search() : self {
		$this->has_search = false;
		$this->search     = '';

		return $this;
	}

	/**
	 * Returns an instance of the data view with a particular sorting applied.
	 *
	 * @since $ver$
	 *
	 * @param Sort|null $sort The sort object.
	 *
	 * @return self The dataview.
	 */
	public function sort( ?Sort $sort ) : self {
		$this->sort = $sort;

		return $this;
	}

	/**
	 * Returns the data needed to set up a WordPress DataViews component.
	 *
	 * @since $ver$
	 *
	 * @return array The data for a WordPress DataViews component.
	 */
	public function to_array() : array {
		return [
			'search'           => $this->has_search,
			'supportedLayouts' => $this->supported_layouts(),
			'paginationInfo'   => $this->pagination->info( $this->data_source() ),
			'view'             => $this->view(),
			'fields'           => $this->dictionary_fields(),
			'data'             => $this->get_data(),
			'actions'          => $this->actions ? $this->actions->to_array() : [],
		];
	}

	/**
	 * Returns the javascript object for a WordPress DataViews component.
	 *
	 * Note: removing "__RAW__ and __ENDRAW__" ensure certain code is provided as javascript, instead of a string.
	 *
	 * @since $ver$
	 * @return string The javascript object.
	 */
	public function to_js( bool $is_pretty = false ) : string {
		$flags = JSON_THROW_ON_ERROR;
		if ( $is_pretty ) {
			$flags |= JSON_PRETTY_PRINT;
		}

		try {
			return preg_replace_callback(
				'/\"__RAW__(.*?)__ENDRAW__\"/s',
				static fn( array $matches ) : string => stripslashes( $matches[1] ),
				json_encode( $this->to_array(), $flags ),
			);
		} catch ( JsonException $e ) {
			return '';
		}
	}

	/**
	 * Makes a single result of a dataview visible within a modal.
	 *
	 * Note: This method adds a primary action to open a single entry template in a modal.
	 *
	 * @since $ver$
	 *
	 * @param array  $fields The fields to show.
	 * @param string $label  The label to call the action.
	 *
	 * @return self The dataview with the view action.
	 */
	public function viewable( array $fields, string $label = 'View' ) : self {
		$this->add_view_fields( ...$fields );

		$actions       = $this->actions ? iterator_to_array( $this->actions ) : [];
		$view_rest_url = Router::get_url( sprintf( 'views/%s/data/{id}', $this->id() ) );

		$view_action = Action::modal( 'view', $label, $view_rest_url, true )
			->primary( 'info' );

		$actions[] = $view_action;

		$this->actions = Actions::of( ...$actions );

		return $this;
	}

	/**
	 * Returns an instance of the dataview which includes a delete action.
	 *
	 * Note: The method adds a primary destructive action, with a confirmation. To change anything to the action, you
	 * can provide a callback which receives (and should return) the action.
	 *
	 * @since $ver$
	 *
	 * @param string        $label    The label to use on the button.
	 * @param callable|null $callback Callback that receives the action as the single argument to perform changes on.
	 *
	 * @return self The dataview with a delete action.
	 */
	public function deletable( string $label = 'Delete', ?callable $callback = null ) : self {
		if (
			! $this->data_source instanceof MutableDataSource
			|| ! $this->data_source->can_delete()
		) {
			return $this;
		}

		$actions         = $this->actions ? iterator_to_array( $this->actions ) : [];
		$delete_rest_url = Router::get_url( sprintf( 'views/%s/data', $this->id() ) );

		$delete_action = Action::ajax( 'delete', $label, $delete_rest_url, 'DELETE', [ 'id' => '{id}' ], true )
			->destructive()
			->bulk()
			->primary( 'trash' )
			->confirm( 'Are you sure you want to delete these items?' );

		if ( $callback ) {
			$delete_action = $callback( $delete_action );
			if ( ! $delete_action instanceof Action ) {
				throw new \InvalidArgumentException( 'The provided callback should return an Action object.' );
			}
		}

		$actions[] = $delete_action;

		$this->actions = Actions::of( ...$actions );

		return $this;
	}

	/**
	 * Adds fields for the single entry view.
	 *
	 * @since $ver$
	 *
	 * @param Field ...$fields The fields.
	 */
	private function add_view_fields( Field ...$fields ) : void {
		$this->view_fields = $fields;
	}
}
