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
			empty( $this->field )
			|| empty( $this->value )
		) {
			throw new InvalidArgumentException( 'Filter needs a field, operator and value' );
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

		if ( is_array( $value ) ) {
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
	 * @return array The filter array.
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
		return new self(
			$array['field'] ?? '',
			$array['operator'] ?? '',
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
			throw new BadMethodCallException( sprintf( 'Static method "%s" not found', $method ) );
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
			Operator::isAll => count( array_intersect( $value, $this->value ) ) === count( $value ),
			Operator::isNone => ! in_array( $value, $this->value, true ),
			Operator::isNotAll => count( array_intersect( $value, $this->value ) ) > count( $value ),
			default => false,
		};
	}
}
