<?php

namespace DataKit\DataView\DataView;

enum View: string {
	case Table = 'table';
	case Grid = 'grid';
	case List = 'list';
}
