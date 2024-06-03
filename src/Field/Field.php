<?php

namespace DataKit\DataView\Field;

use DataKit\DataView\DataView\Filter;
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

	protected function __construct(
		protected string $id,
		protected string $header,
		protected $is_sortable = true,
		protected $is_hideable = true,
		protected array $operators = [],
		protected $is_primary = true,
		protected ?string $default_value = null,
	) {
	}

	public static function create( ...$args ) : static {
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
		return $this->render;
	}

	/**
	 * Create a new instance with the provided filter operators.
	 *
	 * @param string ...$filters The filters.
	 *
	 * @return static A new instance with the filters applied.
	 */
	public function filterable_by( Operator ...$operators ) : static {
		$clone            = clone $this;
		$clone->operators = $operators;

		return $clone;
	}

	public function not_sortable() : static {
		$clone              = clone $this;
		$clone->is_sortable = false;

		return $clone;
	}

	public function sortable() : static {
		$clone              = clone $this;
		$clone->is_sortable = true;

		return $clone;
	}

	public function not_hideable() : static {
		$clone              = clone $this;
		$clone->is_hideable = false;

		return $clone;
	}

	public function hideable() : static {
		$clone              = clone $this;
		$clone->is_hideable = true;

		return $clone;
	}

	public function primary() : static {
		$clone             = clone $this;
		$clone->is_primary = true;

		return $clone;
	}

	public function secondary() : static {
		$clone             = clone $this;
		$clone->is_primary = false;

		return $clone;
	}

	public function hidden() : static {
		$clone            = clone $this;
		$clone->is_hidden = true;

		return $clone;
	}

	public function visible() : static {
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
				static fn( Operator $operator ) : string => $operator->value,
				$this->operators
			),
			'isPrimary' => $this->is_primary,
		];
	}

	public function is_hidden() : bool {
		return $this->is_hidden;
	}

	public function default_value( ?string $default_value ) : static {
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
			'elements'      => $this->get_elements(),
			'filterBy'      => $this->get_filter_by(),
		];
	}

	/**
	 *
	 * @return array{}
	 */
	protected function get_elements() : array {
		return [];
	}

	/**
	 * Sets and validates the elements.
	 * @since $ver$
	 *
	 * @param array $elements The elements.
	 */
	protected function set_elements( array $elements ) : void {
		foreach ( $elements as $key => $element ) {
			if ( is_string( $key ) && is_string( $element ) ) {
				$elements[ $key ] = $element = [
					'label' => $element,
					'value' => $key,
				];
			}

			if (
				! is_array( $element )
				|| ! isset( $element['label'], $element['value'] )
			) {
				throw new InvalidArgumentException( 'An element must have a label and a value.' );
			}
		}
		$this->elements = array_values( $elements );
	}
}
