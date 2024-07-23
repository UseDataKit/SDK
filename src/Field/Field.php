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
 * @phpstan-consistent-constructor
 */
abstract class Field {
	/**
	 * The field ID.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * The label on the header.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $header;

	/**
	 * The render function.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $render = '';

	/**
	 * Whether the field is hidden by default.
	 *
	 * @since $ver$
	 *
	 * @var bool
	 */
	protected bool $is_hidden = false;

	/**
	 * Whether the field is sortable.
	 *
	 * @since $ver$
	 *
	 * @var bool
	 */
	protected bool $is_sortable = true;

	/**
	 * Whether the field is hideable.
	 *
	 * @since $ver$
	 *
	 * @var bool
	 */
	protected bool $is_hideable = true;

	/**
	 * The default value to use if the value is empty.
	 *
	 * @since $ver$
	 *
	 * @var string|null
	 */
	protected ?string $default_value = null;


	/**
	 * The callback to return the value.
	 *
	 * @since $ver$
	 *
	 * @var callable
	 */
	protected $callback;

	/**
	 * The context object for the JavaScript renderer.
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
	 * @param string $id     The field ID.
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
	 *
	 * @return string
	 */
	final public function uuid(): string {
		try {
			return sprintf(
				'%s-%s',
				$this->id(),
				md5( json_encode( [ $this->id, $this->header ], JSON_THROW_ON_ERROR ) ),
			);
		} catch ( JsonException $e ) {
			return 'error';
		}
	}

	/**
	 * Provides a named constructor for easy creation.
	 *
	 * @since $ver$
	 *
	 * @return self The field instance.
	 */
	public static function create( ...$args ) {
		$instance = new static( ...$args );

		if ( ! $instance->render() ) {
			throw new InvalidArgumentException( 'The field requires a `render` option.' );
		}

		return $instance;
	}

	/**
	 * Returns the field's unique identifier.
	 *
	 * @since $ver$
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * The field’s name to be shown in the UI
	 *
	 * @since $ver$
	 * @return string
	 */
	public function header(): string {
		return $this->header;
	}

	/**
	 * Renders the field. Should be any of the field type renderers (e.g., `fields.html`).
	 *
	 * @since $ver$
	 *
	 * @return string
	 */
	public function render(): string {
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
	 * @return self The non-sortable field.
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
	 * @return self The sortable field.
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
	 * @return self The always-visible field.
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
	 * @return self The field that can be hidden.
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
	 * @return self The hidden field.
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
	 * @return self The visible field.
	 */
	public function visible() {
		$clone            = clone $this;
		$clone->is_hidden = false;

		return $clone;
	}

	/**
	 * Sets the callback for the field to alter the value.
	 *
	 * @since $ver$
	 *
	 * @param callable $callback The callback.
	 *
	 * @return self The field.
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
	 * @return self The field with a default value.
	 */
	public function default_value( ?string $default_value ) {
		$clone                = clone $this;
		$clone->default_value = $default_value;

		return $clone;
	}

	/**
	 * Returns whether the field is hidden.
	 *
	 * @since $ver$
	 * @return bool Whether the field is hidden.
	 */
	public function is_hidden(): bool {
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
	 *
	 * @return array<string, mixed> The field configuration.
	 */
	public function to_array(): array {
		return [
			'id'            => $this->id(),
			'header'        => $this->header(),
			'render'        => $this->render(),
			'enableHiding'  => $this->is_hideable,
			'enableSorting' => $this->is_sortable,
		];
	}

	/**
	 * Returns the context needed for the JavaScript part of the field.
	 *
	 * @since $ver$
	 *
	 * @return array[] The context.
	 */
	protected function context(): array {
		return $this->context;
	}

	/**
	 * Returns the default context of the field.
	 *
	 * Note: this should be overwritten on extending field.
	 *
	 * @since $ver$
	 *
	 * @return array The default context values.
	 */
	protected function default_context(): array {
		return [];
	}
}
