<?php

namespace DataKit\DataView\DataView;

use InvalidArgumentException;

/**
 * Represents a valid filter operator.
 * @since $ver$
 *
 * @method static self is()
 * @method static self isNot()
 * @method static self isAny()
 * @method static self isAll()
 * @method static self isNone()
 * @method static self isNotAll()
 */
final class Operator {
	private const is = 'is';
	private const isNot = 'isNot';
	private const isAny = 'isAny';
	private const isAll = 'isAll';
	private const isNone = 'isNone';
	private const isNotAll = 'isNotAll';

	private string $value;

	/**
	 * @return string[]
	 */
	public static function cases() : array {
		return [
			self::is,
			self::isNot,
			self::isAny,
			self::isAll,
			self::isNone,
			self::isNotAll,
		];
	}

	public static function singleCases() : array {
		return [
			self::is,
			self::isNot,
		];
	}

	public static function multiCases() : array {
		return [
			self::isAny,
			self::isAll,
			self::isNone,
			self::isNotAll,
		];
	}

	private function __construct( string $value ) {
		if ( ! in_array( $value, self::cases(), true ) ) {
			throw new \InvalidArgumentException( 'No valid operator provided.' );
		}

		$this->value = $value;
	}

	public static function tryFrom( string $type ) : ?self {
		try {
			return new self ( $type );
		} catch ( \InvalidArgumentException $e ) {
			return null;
		}
	}

	public static function __callStatic( string $method, array $arguments ) : self {
		$type = self::tryFrom( $method );
		if ( ! $type ) {
			throw new InvalidArgumentException( 'No valid operator provided.' );
		}

		return $type;
	}

	/**
	 * @since $ver$
	 */
	public function __toString() : string {
		return $this->value;
	}
}
