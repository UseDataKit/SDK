<?php

namespace DataKit\DataViews;

use InvalidArgumentException;

/**
 * An enum object backed by a string.
 *
 * @since $ver$
 */
abstract class EnumObject {
	/**
	 * The backing value of the enum.
	 *
	 * @since $ver$
	 * @var string
	 */
	protected string $value;

	/**
	 * Returns the available enum cases.
	 *
	 * @since $ver$
	 * @return array<string, string> Cases as key => value.
	 */
	abstract protected static function cases(): array;

	/**
	 * Create the Enum object.
	 *
	 * @since $ver$
	 *
	 * @param string $enum_case The Enum case.
	 *
	 * @throws InvalidArgumentException If the case is invalid.
	 */
	final private function __construct( string $enum_case ) {
		if ( ! isset( static::cases()[ $enum_case ] ) ) {
			throw new InvalidArgumentException( 'No valid Enum option provided.' );
		}

		$this->value = static::cases()[ $enum_case ];
	}

	/**
	 * Constructor to create an Enum by its backing value.
	 *
	 * @since $ver$
	 *
	 * @param string $enum_case The case.
	 *
	 * @return static|null The Enum is valid.
	 */
	final public static function try_from( string $enum_case ) {
		try {
			return new static( $enum_case );
		} catch ( InvalidArgumentException $e ) {
			return null;
		}
	}

	/**
	 * Dynamically create Enums from their constant name, as a method.
	 *
	 * @since $ver$
	 *
	 * @param string $method The case name.
	 * @param array  $_      Unused arguments.
	 *
	 * @return static The Enum object.
	 * @throws InvalidArgumentException
	 */
	final public static function __callStatic( string $method, array $_ ) {
		$type = self::try_from( $method );

		if ( ! $type ) {
			throw new InvalidArgumentException( 'No valid Enum object provided.' );
		}

		return $type;
	}

	/**
	 * Returns the backing value of the Enum.
	 *
	 * @since $ver$
	 */
	public function __toString(): string {
		return $this->as_string();
	}

	/**
	 * Returns the backing value of the Enum.
	 *
	 * @since $ver$
	 */
	public function as_string(): string {
		return $this->value;
	}

	/**
	 * Whether this enum matches another given one.
	 *
	 * @since $ver$
	 *
	 * @param static $other The other value enum.
	 *
	 * @return bool Whether the enums match.
	 */
	public function equals( $other ): bool {
		if ( ! $other instanceof static ) {
			return false;
		}

		return $this->value === $other->value;
	}
}
