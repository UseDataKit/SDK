<?php

namespace DataKit\DataView\Field;

use InvalidArgumentException;

/**
 * @since $ver$
 */
final class EnumField extends Field {
	protected string $render = 'datakit_fields.enum';

	protected function __construct(
		string $id,
		string $header,
		array $elements
	) {
		parent::__construct( $id, $header );

		$this->set_elements( $elements );
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

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function context() : array {
		return array_merge(
			parent::context(),
			[
				'elements' => $this->elements,
			]
		);
	}
}
