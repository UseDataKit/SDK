<?php

namespace DataKit\DataView\DataView;

use InvalidArgumentException;
use ReflectionClass;

/**
 * An enum object backed by a string.
 * @since $ver$
 */
abstract class EnumObject {
	/**
	 * The backing value of the enum.
	 * @since $ver$
	 * @var string
	 */
	protected string $value;

	/**
	 * Returns the available enum cases.
	 * @since $ver$
	 * @return array<string, string> Cases as key => value.
	 */
	protected static function cases() : array {
		return ( new ReflectionClass( static::class ) )->getConstants();
	}

	/**
	 * Create the enum object.
	 * @since $ver$
	 *
	 * @param string $case The enum case.
	 */
	private function __construct( string $case ) {
		if ( ! isset( self::cases()[ $case ] ) ) {
			throw new \InvalidArgumentException( 'No valid enum option provided.' );
		}

		$this->value = self::cases()[ $case ];
	}

	/**
	 * Constructor to create an enum by its backing value.
	 * @since $ver$
	 *
	 * @param string $case The case.
	 *
	 * @return static|null The enum is valid.
	 */
	public static function tryFrom( string $case ) {
		try {
			return new static ( $case );
		} catch ( \InvalidArgumentException $e ) {
			return null;
		}
	}

	/**
	 * Dynamically create enums from their constant name, as a method.
	 * @since $ver$
	 *
	 * @param string $method The case name.
	 * @param array $_ Unused arguments.
	 *
	 * @return static The enum object.
	 */
	public static function __callStatic( string $method, array $_ ) {
		$type = self::tryFrom( $method );

		if ( ! $type ) {
			throw new InvalidArgumentException( 'No valid enum object provided.' );
		}

		return $type;
	}

	/**
	 * Returns the backing value of the enum.
	 * @since $ver$
	 */
	public function __toString() : string {
		return $this->as_string();
	}

	/**
	 * Returns the backing value of the enum.
	 * @since $ver$
	 */
	public function as_string() : string {
		return $this->value;
	}
}
