<?php

namespace DataKit\DataViews\DataView;

use DataKit\DataViews\Field\Field;
use InvalidArgumentException;

/**
 * Represents the sorting applied to a DataView.
 *
 * @since $ver$
 */
final class Sort {
	/**
	 * The sorting options.
	 *
	 * @since $ver$
	 */
	public const ASC  = 'ASC';
	public const DESC = 'DESC';

	/**
	 * The field to sort.
	 *
	 * @since $ver$
	 * @var string
	 */
	private string $field;

	/**
	 * The direction to sort in.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $direction;

	/**
	 * Creates a sort instance.
	 *
	 * @since $ver$
	 *
	 * @param string $field     The field name.
	 * @param string $direction The direction.
	 */
	private function __construct( string $field, string $direction ) {
		$this->direction = strtoupper( $direction );
		$this->field     = Field::normalize( $field );

		if (
			( empty( $field ) && '0' !== $field )
			|| ! in_array( $this->direction, [ self::ASC, self::DESC ], true )
		) {
			throw new InvalidArgumentException( 'A sort consists of a field and a direction.' );
		}
	}

	/**
	 * Serializes the sort as an array.
	 *
	 * @since $ver$
	 *
	 * @return array<string, string> The serialized sort object.
	 */
	public function to_array(): array {
		return [
			'field'     => $this->field,
			'direction' => $this->direction,
		];
	}

	/**
	 * Creates a sort object from an array.
	 *
	 * @since $ver$
	 *
	 * @param array $field_array The array.
	 *
	 * @return self The sort object.
	 */
	public static function from_array( array $field_array ): self {
		return new self( $field_array['field'] ?? '', $field_array['direction'] ?? '' );
	}

	/**
	 * Creates a sort object for the provided field with an ascending direction.
	 *
	 * @since $ver$
	 *
	 * @param string $field The field name.
	 *
	 * @return self The sort object.
	 */
	public static function asc( string $field ): self {
		return new self( $field, self::ASC );
	}

	/**
	 * Creates a sort object for the provided field with a descending direction.
	 *
	 * @since $ver$
	 *
	 * @param string $field The field name.
	 *
	 * @return self The sort object.
	 */
	public static function desc( string $field ): self {
		return new self( $field, self::DESC );
	}
}
