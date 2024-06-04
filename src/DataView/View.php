<?php

namespace DataKit\DataView\DataView;

/**
 * Represents the valid view types.
 * @since $ver$
 * @method static self Table()
 * @method static self Grid()
 * @method static self List()
 */
final class View extends EnumObject {
	private const Table = 'table';
	private const Grid = 'grid';
	private const List = 'list';
}
