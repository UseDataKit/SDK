<?php

namespace DataKit\DataViews\DataView;

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
final class Operator extends EnumObject {
	private const is = 'is';
	private const isNot = 'isNot';
	private const isAny = 'isAny';
	private const isAll = 'isAll';
	private const isNone = 'isNone';
	private const isNotAll = 'isNotAll';

	/**
	 * A list of operators applicable to single values.
	 * @since $ver$
	 * @return self[] The operators.
	 */
	public static function singleCases() : array {
		return [
			self::is(),
			self::isNot(),
		];
	}

	/**
	 * A list of operators applicable to multiple values.
	 * @since $ver$
	 * @return self[] The operators.
	 */
	public static function multiCases() : array {
		return [
			self::isAny(),
			self::isAll(),
			self::isNone(),
			self::isNotAll(),
		];
	}
}
