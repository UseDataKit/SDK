<?php

namespace DataKit\DataViews\DataView;

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
	abstract protected static function cases() : array;

	/**
	 * Create the enum object.
	 *
	 * @since $ver$
	 *
	 * @param string $case The enum case.
	 */
	final private function __construct( string $case ) {
		if ( ! isset( static::cases()[ $case ] ) ) {
			throw new \InvalidArgumentException( 'No valid enum option provided.' );
		}

		$this->value = static::cases()[ $case ];
	}

	/**
	 * Constructor to create an enum by its backing value.
	 *
	 * @since $ver$
	 *
	 * @param string $case The case.
	 *
	 * @return static|null The enum is valid.
	 */
	final public static function tryFrom( string $case ) {
		try {
			return new static ( $case );
		} catch ( \InvalidArgumentException $e ) {
			return null;
		}
	}

	/**
	 * Dynamically create enums from their constant name, as a method.
	 *
	 * @since $ver$
	 *
	 * @param string $method The case name.
	 * @param array  $_      Unused arguments.
	 *
	 * @return static The enum object.
	 */
	final public static function __callStatic( string $method, array $_ ) {
		$type = self::tryFrom( $method );

		if ( ! $type ) {
			throw new InvalidArgumentException( 'No valid enum object provided.' );
		}

		return $type;
	}

	/**
	 * Returns the backing value of the enum.
	 *
	 * @since $ver$
	 */
	public function __toString() : string {
		return $this->as_string();
	}

	/**
	 * Returns the backing value of the enum.
	 *
	 * @since $ver$
	 */
	public function as_string() : string {
		return $this->value;
	}
}
