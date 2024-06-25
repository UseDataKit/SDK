<?php

namespace DataKit\DataViews\DataView;

use InvalidArgumentException;

/**
 * Represents the sorting applied to a data view.
 * @since $ver$
 */
final class Sort {
	/**
	 * The sorting options.
	 * @since $ver$
	 */
	public const ASC = 'ASC';
	public const DESC = 'DESC';
	private string $field;
	private string $direction;

	/**
	 * Creates a sort instance.
	 * @since $ver$
	 *
	 * @param string $direction The direction.
	 * @param string $field The field name.
	 */
	private function __construct( string $field, string $direction ) {
		$this->direction = strtoupper( $direction );
		$this->field     = $field;

		if (
			empty( $field )
			|| ! in_array( $this->direction, [ self::ASC, self::DESC ], true )
		) {
			throw new InvalidArgumentException( 'A sort consists of a field and a direction.' );
		}
	}

	/**
	 * Serialize the sort as an array.
	 * @since $ver$
	 * @return array<string, string> The serialized sort object.
	 */
	public function to_array() : array {
		return [
			'field'     => $this->field,
			'direction' => $this->direction,
		];
	}

	/**
	 * Create a sort object from an array.
	 * @since $ver$
	 *
	 * @param array{field: string, direction: string} $array The array.
	 *
	 * @return self The sort object.
	 */
	public static function from_array( array $array ) : self {
		return new self( $array['field'] ?? '', $array['direction'] ?? '' );
	}

	/**
	 * Creates a sort object for the provided field with an ascending direction.
	 * @since $ver$
	 *
	 * @param string $field The field name.
	 *
	 * @return self The sort object.
	 */
	public static function asc( string $field ) : self {
		return new self( $field, self::ASC );
	}

	/**
	 * Creates a sort object for the provided field with a descending direction.
	 * @since $ver$
	 *
	 * @param string $field The field name.
	 *
	 * @return self The sort object.
	 */
	public static function desc( string $field ) : self {
		return new self( $field, self::DESC );
	}
}
