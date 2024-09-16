<?php

namespace DataKit\DataViews\DataView;

use DataKit\DataViews\EnumObject;

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
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	protected static function cases(): array {
		return [
			'is'       => 'is',
			'isNot'    => 'isNot',
			'isAny'    => 'isAny',
			'isAll'    => 'isAll',
			'isNone'   => 'isNone',
			'isNotAll' => 'isNotAll',
		];
	}

	/**
	 * Returns the list of operators applicable to single values.
	 *
	 * @since $ver$
	 *
	 * @return self[] The operators.
	 */
	public static function singleCases(): array {
		return [
			self::is(),
			self::isNot(),
		];
	}

	/**
	 * Return the list of operators applicable to multiple values.
	 *
	 * @since $ver$
	 *
	 * @return self[] The operators.
	 */
	public static function multiCases(): array {
		return [
			self::isAny(),
			self::isAll(),
			self::isNone(),
			self::isNotAll(),
		];
	}
}
