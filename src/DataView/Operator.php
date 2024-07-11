<?php

namespace DataKit\DataViews\DataView;

/**
 * Represents a valid filter operator.
 *
 * @link  https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#operators
 *
 * @since $ver$
 *
 * @method static self is() The item’s field is equal to a single value.
 * @method static self isNot() The item’s field is not equal to a single value.
 * @method static self isAny() The item’s field is present in a list of values.
 * @method static self isAll() The item’s field has all of the values in the list.
 * @method static self isNone() The item’s field is not present in a list of values.
 * @method static self isNotAll() The item’s field doesn't have all of the values in the list.
 */
final class Operator extends EnumObject {
	/**
	 * The possible operator types.
	 *
	 * @since $ver$
	 */
	private const is = 'is';
	private const isNot = 'isNot';
	private const isAny = 'isAny';
	private const isAll = 'isAll';
	private const isNone = 'isNone';
	private const isNotAll = 'isNotAll';

	/**
	 * A list of operators applicable to single values.
	 *
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
	 *
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
