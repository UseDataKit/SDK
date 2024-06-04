<?php

namespace DataKit\DataView\DataView;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * Represents a filter on Data Views and Data sources.
 * @since $ver$
 *
 * @method static self is( string $field, int|string|float|bool $value )
 * @method static self isNot( string $field, int|string|float|bool $value )
 * @method static self isAny( string $field, array $value )
 * @method static self isAll( string $field, array $value )
 * @method static self isNone( string $field, array $value )
 * @method static self isNotAll( string $field, array $value )
 */
final class Filter {
	private string $field;
	private Operator $operator;

	private $value;

	/**
	 * Creates the filter.
	 * @since $ver$
	 *
	 * @param string $field The field name.
	 * @param Operator $operator The operator.
	 * @param string|array $value The filter value.
	 */
	private function __construct(
		string $field,
		Operator $operator,
		$value
	) {
		$this->value    = $value;
		$this->operator = $operator;
		$this->field    = $field;

		if ( empty( $field ) || empty( $value ) ) {
			throw new InvalidArgumentException( 'Filter needs a field, operator and value' );
		}

		if (
			! is_array( $value )
			&& in_array( (string) $operator, Operator::multiCases(), true )
		) {
			throw new BadMethodCallException( '"value" parameter expects array.' );
		}

		if (
			! self::is_stringable( $value )
			&& in_array( (string) $operator, Operator::singleCases(), true )
		) {
			throw new BadMethodCallException( '"value" parameter expects string.' );
		}
	}

	/**
	 * Recursively flattens the value.
	 * @since $ver$
	 *
	 * @param mixed $value The original value.
	 *
	 * @return mixed The flattened value.
	 */
	private static function flatten_value( $value ) {
		if ( self::is_stringable( $value ) ) {
			return (string) $value;
		}

		if ( is_iterable( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::flatten_value( $item );
			}

			return $value;
		}

		return null;
	}

	private static function is_stringable( $value ) : bool {
		if ( is_scalar( $value ) || is_null( $value ) ) {
			return true;
		}

		if ( is_object( $value ) && method_exists( $value, '__toString' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Serializes a Filter to an array.
	 * @since $ver$
	 * @return array{field: string, operator: string, value: string} The filter array.
	 */
	public function to_array() : array {
		return [
			'field'    => $this->field,
			'operator' => (string) $this->operator,
			'value'    => self::flatten_value( $this->value ),
		];
	}

	/**
	 * Creates a filter from an array.
	 *
	 * @since $ver$
	 *
	 * @param array $array The array.
	 *
	 * @return self The filter.
	 */
	public static function from_array( array $array ) : self {
		$operator = Operator::tryFrom( $array['operator'] ?? '' );
		if ( ! $operator ) {
			throw new \InvalidArgumentException( 'No valid operator provided.' );
		}

		return new self(
			$array['field'] ?? '',
			$operator,
			$array['value'] ?? '',
		);
	}

	/**
	 * Creates special static constructors based on the available operators.
	 * @since $ver$
	 *
	 * @param string $method The method name.
	 * @param array $arguments The arguments for the method.
	 *
	 * @return self The filter.
	 */
	public static function __callStatic( string $method, array $arguments ) : self {
		$operator = Operator::tryFrom( $method );

		if ( ! $operator ) {
			throw new BadMethodCallException( sprintf( 'Static method "%s" not found.', $method ) );
		}

		if ( count( $arguments ) !== 2 ) {
			throw new BadMethodCallException(
				sprintf( 'Method "%s" expects exactly 2 arguments, %d given.', $method, count( $arguments ) )
			);
		}

		return new self( $arguments[0] ?? '', $operator, $arguments[1] ?? '' );
	}

	/**
	 * Whether the given value matches the filter.
	 * @since $ver$
	 *
	 * @param array $data The dataset to match against.
	 *
	 * @return bool Whether the value matches the filter.
	 * @todo this may need to be moved to a separate class.
	 */
	public function matches( array $data ) : bool {
		$value = $data[ $this->field ] ?? null;
		if ( ! $value ) {
			return false;
		}

		switch ( $this->operator ) {
			case Operator::is():
				return $this->value === $value;
			case Operator::isNot():
				return $this->value !== $value;
			case Operator::isAny():
				return in_array( $value, $this->value, true );
			case Operator::isAll():
				return count( array_intersect( $value, $this->value ) ) === count( $this->value );
			case Operator::isNone():
				return count( array_intersect( $value, $this->value ) ) === 0;
			case Operator::isNotAll():
				return count( array_intersect( $value, $this->value ) ) !== count( $this->value );
			default:
				return false;
		}
	}
}
