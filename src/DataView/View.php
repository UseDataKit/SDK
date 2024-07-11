<?php

namespace DataKit\DataViews\DataView;

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
	 * The possible view types.
	 *
	 * @since $ver$
	 */
	private const Table = 'table';
	private const Grid = 'grid';
	private const List = 'list';
}
