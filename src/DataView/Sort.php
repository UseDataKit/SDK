<?php

namespace DataKit\DataView\DataView;

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

	/**
	 * Creates a sort instance.
	 * @since $ver$
	 *
	 * @param string $direction The direction.
	 * @param string $field The field name.
	 */
	private function __construct( private string $field, private string $direction ) {
		if ( empty( $this->field ) ) {
			throw new InvalidArgumentException( 'A sort consists of a field and a direction.' );
		}
	}

	/**
	 * Serialize the sort as an array.
	 * @since $ver$
	 * @return array<string, string> The serialized sort object.
	 */
	public function toArray() : array {
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
	public static function fromArray( array $array ) : self {
		if (
			! isset( $array['field'], $array['direction'] )
			|| ! in_array( strtoupper( $array['direction'] ), [ self::ASC, self::DESC ], true )
		) {
			throw new InvalidArgumentException( 'A sort consists of a field and a direction.' );
		}

		return new self( $array['field'], strtoupper( $array['direction'] ) );
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
