<?php

namespace DataKit\DataView\Field;

use DataKit\DataView\DataView\Operator;
use InvalidArgumentException;

/**
 * Represents an (immutable) field on the view.
 * @since $ver$
 */
abstract class Field {
	protected string $render = '';
	protected bool $is_hidden = false;
	protected array $elements = [];
	protected string $id;
	protected string $header;
	protected $is_sortable = true;
	protected $is_hideable = true;
	protected array $operators = [];
	protected $is_primary = true;
	protected ?string $default_value = null;

	protected function __construct(
		string $id,
		string $header,
		$is_sortable = true,
		$is_hideable = true,
		array $operators = [],
		$is_primary = true,
		?string $default_value = null
	) {
		$this->default_value = $default_value;
		$this->is_primary    = $is_primary;
		$this->operators     = $operators;
		$this->is_hideable   = $is_hideable;
		$this->is_sortable   = $is_sortable;
		$this->header        = $header;
		$this->id            = $id;
	}

	/**
	 * @return static
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
	 * @since $ver$
	 * @return string
	 */
	public function id() : string {
		return $this->id;
	}

	/**
	 * The field’s name to be shown in the UI
	 * @since $ver$
	 * @return string
	 */
	public function header() : string {
		return $this->header;
	}

	/**
	 * Function that renders the field. Should be any of the field type renderers; e.g. `fields.html`.
	 * @since $ver$
	 * @return string
	 */
	public function render() : string {
		$function = sprintf(
			'( data ) => %s(%s, data, %s)',
			$this->render,
			$this->id,
			json_encode([]),
		);
		return '__RAW__'.$function.'__ENDRAW__';
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
	public function not_hideable() {
		$clone              = clone $this;
		$clone->is_hideable = false;

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
		$clone            = clone $this;
		$clone->is_hidden = true;

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
				$this->operators
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

	public function value( array $data ) {
		return $data[ $this->id() ] ?? $this->default_value ?: $this->default_value;
	}

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
}
