<?php

namespace DataKit\DataView\Field;

use InvalidArgumentException;

/**
 * @since $ver$
 */
final class EnumField extends Field {
	protected string $render = 'datakit_fields.enum';
	protected string $id;
	protected string $header;
	protected $is_sortable;
	protected $is_hideable;
	protected array $operators;
	protected $is_primary;
	protected ?string $default_value = null;

	protected function __construct(
		string $id,
		string $header,
		array $elements,
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

		parent::__construct( $id, $header, $is_sortable, $is_hideable, $operators, $is_primary, $default_value );

		$this->set_elements( $elements );
	}

	/**
	 *
	 * @return array{}
	 */
	protected function get_elements() : array {
		return [];
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function toArray() : array {
		return array_merge( parent::toArray(), [
			'elements' => $this->elements,
		] );
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
