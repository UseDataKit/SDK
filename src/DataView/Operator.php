<?php

namespace DataKit\DataView\DataView;

/**
 * Represents a valid filter operator.
 * @since $ver$
 */
enum Operator: string {
	case is = 'is';
	case isNot = 'isNot';
	case isAny = 'isAny';
	case isAll = 'isAll';
	case isNone = 'isNone';
	case isNotAll = 'isNotAll';
}
