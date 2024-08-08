<?php

namespace DataKit\DataViews\DataView;

use BadMethodCallException;
use DataKit\DataViews\Field\Field;
use InvalidArgumentException;

/**
 * Represents a filter on DataViews and Data sources.
 *
 * @since $ver$
 *
 * @method static self is( string $field, int|string|float|bool $value )
 * @method static self isNot( string $field, int|string|float|bool $value )
 * @method static self isAny( string $field, array $value )
 * @method static self isAll( string $field, array $value )
 * @method static self isNone( string $field, array $value )
 * @method static self isNotAll( string $field, array $value )
 *
 * @phpstan-type FilterShape array{field: string, operator: string, value: string}
 */
final class Filter {
	/**
	 * The field to filter on.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $field;

	/**
	 * The filter operation.
	 *
	 * @since $ver$
	 *
	 * @var Operator
	 */
	private Operator $operator;

	/**
	 * The filter value.
	 *
	 * @since $ver$
	 *
	 * @var array|string
	 */
	private $value;

	/**
	 * Creates the filter.
	 *
	 * @since $ver$
	 *
	 * @param string       $field    The field name.
	 * @param Operator     $operator The operator.
	 * @param string|array $value    The filter value.
	 */
	private function __construct(
		string $field,
		Operator $operator,
		$value
	) {
		$this->value    = $value;
		$this->operator = $operator;
		$this->field    = Field::normalize( $field );

		if ( empty( $field ) ) {
			throw new InvalidArgumentException( 'Filter needs a field, operator and value.' );
		}

		// phpcs:disable WordPress.PHP.StrictInArray.FoundNonStrictFalse
		if (
			! is_array( $value )
			&& in_array( $operator, Operator::multiCases(), false )
		) {
			throw new BadMethodCallException( '"value" parameter expects array.' );
		}

		if (
			! self::is_stringable( $value )
			&& in_array( $operator, Operator::singleCases(), false )
		) {
			throw new BadMethodCallException( '"value" parameter expects string.' );
		}
	}

	/**
	 * Recursively flattens the value.
	 *
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

	/**
	 * Returns whether the value is a string or can be cast to a string.
	 *
	 * @since $ver$
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return bool Whether the value can be cast to a string.
	 */
	private static function is_stringable( $value ): bool {
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
	 *
	 * @since $ver$
	 *
	 * @return FilterShape The filter array.
	 */
	public function to_array(): array {
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
	 * @param array $filter_array The array.
	 *
	 * @return self The filter.
	 */
	public static function from_array( array $filter_array ): self {
		$operator = Operator::try_from( $filter_array['operator'] ?? '' );

		if ( ! $operator ) {
			throw new \InvalidArgumentException( 'No valid operator provided.' );
		}

		return new self(
			$filter_array['field'] ?? '',
			$operator,
			$filter_array['value'] ?? ( in_array( $operator, Operator::multiCases(), false ) ? [] : '' ),
		);
	}

	/**
	 * Creates special static constructors based on the available operators.
	 *
	 * @since $ver$
	 *
	 * @param string $method    The method name.
	 * @param array  $arguments The arguments for the method.
	 *
	 * @return self The filter.
	 */
	public static function __callStatic( string $method, array $arguments ): self {
		$operator = Operator::try_from( $method );

		if ( ! $operator ) {
			throw new BadMethodCallException( sprintf( 'Static method "%s" not found.', $method ) );
		}

		if ( count( $arguments ) !== 2 ) {
			throw new BadMethodCallException(
				sprintf( 'Method "%s" expects exactly 2 arguments, %d given.', $method, count( $arguments ) ),
			);
		}

		return new self( $arguments[0] ?? '', $operator, $arguments[1] ?? '' );
	}

	/**
	 * Returns whether the given value matches the filter.
	 *
	 * @since $ver$
	 *
	 * @param array $data The data set to match against.
	 *
	 * @return bool Whether the value matches the filter.
	 */
	public function matches( array $data ): bool {
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
