<?php

namespace DataKit\DataViews\Field;

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
	 * The default value to use if the value is empty.
	 *
	 * @since $ver$
	 * @var string|null
	 */
	protected ?string $default_value = null;


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
	 * Returns a new instance of the field that is not sortable.
	 *
	 * @since $ver$
	 *
	 * @return static The field which is *not* sortable.
	 */
	public function not_sortable() {
		$clone              = clone $this;
		$clone->is_sortable = false;

		return $clone;
	}

	/**
	 * Returns a new instance of the field that is sortable.
	 *
	 * @since $ver$
	 *
	 * @return static The field which is sortable.
	 */
	public function sortable() {
		$clone              = clone $this;
		$clone->is_sortable = true;

		return $clone;
	}

	/**
	 * Returns a new instance of the field that cannot be hidden.
	 *
	 * @since $ver$
	 *
	 * @return static The field which is always visible.
	 */
	public function always_visible() {
		$clone              = clone $this;
		$clone->is_hideable = false;
		$clone->is_hidden   = false;

		return $clone;
	}

	/**
	 * Returns a new instance of the field that can be hidden.
	 *
	 * @since $ver$
	 *
	 * @return static The field which can be hidden.
	 */
	public function hideable() {
		$clone              = clone $this;
		$clone->is_hideable = true;

		return $clone;
	}

	/**
	 * Returns a new instance of the field that is hidden by default.
	 *
	 * @since $ver$
	 *
	 * @return static The field which is hidden.
	 */
	public function hidden() {
		$clone              = clone $this;
		$clone->is_hidden   = true;
		$clone->is_hideable = true;

		return $clone;
	}

	/**
	 * Returns a new instance of the field that is visible.
	 *
	 * @since $ver$
	 *
	 * @return static The field which is visible.
	 */
	public function visible() {
		$clone            = clone $this;
		$clone->is_hidden = false;

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
	 * Returns a new instance with a default value if the value is empty.
	 *
	 * @since $ver$
	 *
	 * @return static The field with a default value.
	 */
	public function default_value( ?string $default_value ) {
		$clone                = clone $this;
		$clone->default_value = $default_value;

		return $clone;
	}

	/**
	 * Whether the field is hidden.
	 *
	 * @since $ver$
	 * @return bool Whether the field is hidden.
	 */
	public function is_hidden() : bool {
		return $this->is_hidden;
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
		return ( $this->callback )( $this->id(), $data ) ?? '' ?: $this->default_value;
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
	 * Note: this should be overwritten on extending field.
	 *
	 * @since $ver$
	 * @return array The default context values.
	 */
	protected function default_context() : array {
		return [];
	}
}
