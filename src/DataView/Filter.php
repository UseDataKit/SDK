<?php

namespace DataKit\DataView\DataView;

use BadMethodCallException;
use InvalidArgumentException;
use Stringable;

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
	/**
	 * Creates the filter.
	 * @since $ver$
	 *
	 * @param string $field The field name.
	 * @param Operator $operator The operator.
	 * @param string $value The filter value.
	 */
	private function __construct(
		private string $field,
		private Operator $operator,
		private mixed $value
	) {
		if (
			empty( $field )
			|| empty( $value )
		) {
			throw new InvalidArgumentException( 'Filter needs a field, operator and value' );
		}

		if (
			! is_array( $value )
			&& in_array( $operator, [ Operator::isNone, Operator::isAll, Operator::isAny, Operator::isNotAll ], true )
		) {
			throw new BadMethodCallException(
				sprintf( '"value" parameter expects array, %s given.', get_debug_type( $value ) )
			);
		}

		if (
			( ! is_scalar( $value ) && ! ( $value instanceof Stringable ) )
			&& in_array( $operator, [ Operator::is, Operator::isNot ], true )
		) {
			throw new BadMethodCallException(
				sprintf( '"value" parameter expects string, %s given.', get_debug_type( $value ) )
			);
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
	private static function flatten_value( mixed $value ) : mixed {
		if ( $value instanceof Stringable ) {
			return (string) $value;
		}

		if ( is_iterable( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::flatten_value( $item );
			}

			return $value;
		}

		if ( is_scalar( $value ) ) {
			return $value;
		}

		return null;
	}

	/**
	 * Serializes a Filter to an array.
	 * @since $ver$
	 * @return array{field: string, operator: string, value: string} The filter array.
	 */
	public function to_array() : array {
		return [
			'field'    => $this->field,
			'operator' => $this->operator->value,
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

		return match ( $this->operator ) {
			Operator::is => $this->value === $value,
			Operator::isNot => $this->value !== $value,
			Operator::isAny => in_array( $value, $this->value, true ),
			Operator::isAll => count( array_intersect( $value, $this->value ) ) === count( $this->value ),
			Operator::isNone => count( array_intersect( $value, $this->value ) ) === 0,
			Operator::isNotAll => count( array_intersect( $value, $this->value ) ) !== count( $this->value ),
			default => false,
		};
	}
}
