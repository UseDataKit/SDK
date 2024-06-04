<?php

namespace DataKit\DataView\DataView;

/**
 * Represents the valid view types.
 * @since $ver$
 */
enum View: string {
	case Table = 'table';
	case Grid = 'grid';
	case List = 'list';
}
