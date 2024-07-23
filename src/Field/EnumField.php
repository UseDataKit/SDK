<?php

namespace DataKit\DataViews\Field;

use InvalidArgumentException;

/**
 * Represents a field with elements that renders the label of the corresponding element.
 *
 * @since $ver$
 */
final class EnumField extends FilterableField {
	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $render = 'datakit_fields.html';

	/**
	 * Contains the list of elements.
	 *
	 * @since $ver$
	 *
	 * @var array{value:string, label:string}[].
	 */
	protected array $elements = [];

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
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
	 *
	 * @since $ver$
	 */
	public function to_array(): array {
		return array_merge(
			parent::to_array(),
			[
				'elements' => array_values( $this->elements ),
			],
		);
	}

	/**
	 * Sets and validates the elements.
	 *
	 * @since $ver$
	 *
	 * @param array $elements The elements.
	 */
	protected function set_elements( array $elements ): void {
		foreach ( $elements as $key => $element ) {
			if ( is_string( $key ) && is_string( $element ) ) {
				$element = [
					'label' => $element,
					'value' => $key,
				];

				$elements[ $key ] = $element;
			}

			if (
				! is_array( $element )
				|| ! isset( $element['label'], $element['value'] )
			) {
				throw new InvalidArgumentException( 'An element must have a label and a value.' );
			}
		}

		$this->elements = $elements;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get_value( array $data ) {
		$value = parent::get_value( $data );

		return $this->elements[ $value ]['label'] ?? $value;
	}
}
