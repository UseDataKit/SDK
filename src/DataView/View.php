<?php

namespace DataKit\DataViews\DataView;

use DataKit\DataViews\EnumObject;

/**
 * Represents the valid view types.
 *
 * @see   https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#layouts
 *
 * @since $ver$
 * @method static self Table() A table layout.
 * @method static self Grid() A grid layout.
 * @method static self List() A list layout.
 */
final class View extends EnumObject {
	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	protected static function cases(): array {
		return [
			'Table' => 'table',
			'Grid'  => 'grid',
			'List'  => 'list',
		];
	}
}
