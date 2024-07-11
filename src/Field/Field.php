<?php

namespace DataKit\DataViews\Field;

use DataKit\DataViews\DataView\Operator;
use InvalidArgumentException;
use JsonException;

/**
 * Represents an (immutable) field on the view.
 *
 * @link  https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#fields-object
 *
 * @since $ver$
 */
abstract class Field {
	/**
	 * The field id.
	 *
	 * @since $ver$
	 * @var string
	 */
	protected string $id;

	/**
	 * The label on the header.
	 *
	 * @since $ver$
	 * @var string
	 */
	protected string $header;

	/**
	 * The render function.
	 *
	 * @since $ver$
	 * @var string
	 */
	protected string $render = '';

	/**
	 * Whether the field is hidden by default.
	 *
	 * @since $ver$
	 * @var bool
	 */
	protected bool $is_hidden = false;

	/**
	 * Whether the field is sortable.
	 *
	 * @since $ver$
	 * @var bool
	 */
	protected bool $is_sortable = true;

	/**
	 * Whether the field is hideable.
	 *
	 * @since $ver$
	 * @var bool
	 */
	protected bool $is_hideable = true;

	/**
	 * Whether the fields filter is a primary filter.
	 *
	 * @since $ver$
	 * @var bool
	 */
	protected bool $is_primary = true;

	/**
	 * The default value to use if the value is empty.
	 *
	 * @since $ver$
	 * @var string|null
	 */
	protected ?string $default_value = null;

	/**
	 * The filter operators.
	 *
	 * @since $ver$
	 * @var array
	 */
	protected array $operators = [];

	/**
	 * The callback to return the value.
	 *
	 * @since $ver$
	 * @var callable
	 */
	protected $callback;

	/**
	 * The context object for the javascript renderer.
	 *
	 * @since $ver$
	 * @var array
	 */
	protected array $context = [];

	/**
	 * Creates the field.
	 *
	 * @since $ver$
	 *
	 * @param string $id     The field id.
	 * @param string $header The field label.
	 */
	protected function __construct(
		string $id,
		string $header
	) {
		$this->header = $header;
		$this->id     = $id;

		$this->callback = static fn( string $id, array $data ) => $data[ $id ] ?? null;
		$this->context  = $this->default_context();
	}

	/**
	 * Returns a unique string for this field instance.
	 *
	 * @since $ver$
	 * @return string
	 */
	final public function uuid() : string {
		return sprintf( '%s-%s',
			$this->id(),
			md5( serialize( [ $this->id, $this->header ] ) ),
		);
	}

	/**
	 * Named constructor for easy creation.
	 *
	 * @since $ver$
	 * @return static The field instance.
	 */
	public static function create( ...$args ) {
		$instance = new static( ... $args );

		if ( ! $instance->render() ) {
			throw new InvalidArgumentException( 'The field requires a `render` option.' );
		}

		return $instance;
	}

	/**
	 * Unique identifier for the field.
	 *
	 * @since $ver$
	 * @return string
	 */
	public function id() : string {
		return $this->id;
	}

	/**
	 * The field’s name to be shown in the UI
	 *
	 * @since $ver$
	 * @return string
	 */
	public function header() : string {
		return $this->header;
	}

	/**
	 * Function that renders the field. Should be any of the field type renderers; e.g. `fields.html`.
	 *
	 * @since $ver$
	 * @return string
	 */
	public function render() : string {
		try {
			$function = sprintf(
				'( data ) => %s(%s, data, %s)',
				$this->render,
				json_encode( $this->uuid(), JSON_THROW_ON_ERROR ),
				json_encode( $this->context(), JSON_THROW_ON_ERROR ),
			);
		} catch ( JsonException $e ) {
			return '';
		}

		return '__RAW__' . $function . '__ENDRAW__';
	}

	/**
	 * Create a new instance with the provided filter operators.
	 *
	 * @param Operator ...$operators The operators.
	 *
	 * @return static A new instance with the filters applied.
	 */
	public function filterable_by( Operator ...$operators ) {
		$clone            = clone $this;
		$clone->operators = $operators;

		return $clone;
	}

	/**
	 * @return static
	 */
	public function not_sortable() {
		$clone              = clone $this;
		$clone->is_sortable = false;

		return $clone;
	}

	/**
	 * @return static
	 */
	public function sortable() {
		$clone              = clone $this;
		$clone->is_sortable = true;

		return $clone;
	}

	/**
	 * @return static
	 */
	public function always_visible() {
		$clone              = clone $this;
		$clone->is_hideable = false;
		$clone->is_hidden   = false;

		return $clone;
	}

	/**
	 * @return static
	 */
	public function hideable() {
		$clone              = clone $this;
		$clone->is_hideable = true;

		return $clone;
	}

	/**
	 * @return static
	 */
	public function primary() {
		$clone             = clone $this;
		$clone->is_primary = true;

		return $clone;
	}

	/**
	 * @return static
	 */
	public function secondary() {
		$clone             = clone $this;
		$clone->is_primary = false;

		return $clone;
	}

	/**
	 * @return static
	 */
	public function hidden() {
		$clone              = clone $this;
		$clone->is_hidden   = true;
		$clone->is_hideable = true;

		return $clone;
	}

	/**
	 * @return static
	 */
	public function visible() {
		$clone            = clone $this;
		$clone->is_hidden = false;

		return $clone;
	}

	private function get_filter_by() : ?array {
		if ( ! $this->operators ) {
			return null;
		}

		return [
			'operators' => array_map(
				static fn( Operator $operator ) : string => (string) $operator,
				$this->operators,
			),
			'isPrimary' => $this->is_primary,
		];
	}

	public function is_hidden() : bool {
		return $this->is_hidden;
	}

	/**
	 * @return static
	 */
	public function default_value( ?string $default_value ) {
		$clone                = clone $this;
		$clone->default_value = $default_value;

		return $clone;
	}

	/**
	 * Set the callback for the field to alter the value.
	 *
	 * @since $ver$
	 *
	 * @param callable $callback The callback.
	 *
	 * @return static The field.
	 */
	public function callback( callable $callback ) {
		$clone           = clone $this;
		$clone->callback = $callback;

		return $clone;
	}

	/**
	 * Returns the value of the field on the provided data set.
	 *
	 * @since $ver$
	 *
	 * @param array $data The data set.
	 *
	 * @return mixed The value.
	 */
	public function get_value( array $data ) {
		return ( $this->callback )( $this->id(), $data ) ?? $this->default_value;
	}

	/**
	 * Returns the field as an array object.
	 *
	 * @since $ver$
	 * @return array[] The field configuration.
	 */
	public function toArray() : array {
		return [
			'id'            => $this->id(),
			'header'        => $this->header(),
			'render'        => $this->render(),
			'enableHiding'  => $this->is_hideable,
			'enableSorting' => $this->is_sortable,
			'filterBy'      => $this->get_filter_by(),
		];
	}

	/**
	 * Returns the context needed for the javascript part of the field.
	 *
	 * @since $ver$
	 * @return array[] The context.
	 */
	protected function context() : array {
		return $this->context;
	}

	/**
	 * Returns the default context of the field.
	 *
	 * @since $ver$
	 * @return array
	 */
	protected function default_context() : array {
		return [];
	}
}
