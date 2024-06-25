<?php

namespace DataKit\DataViews\DataView;

use ArrayIterator;
use DataKit\DataViews\Action\Action;
use InvalidArgumentException;
use IteratorAggregate;

final class Actions implements IteratorAggregate {
	private array $actions;

	private function __construct( Action ...$actions ) {
		$this->actions = $actions;
	}

	public static function of( Action ...$actions ) : self {
		if ( ! $actions ) {
			throw new InvalidArgumentException( 'No actions provided.' );
		}

		return new self( ...$actions );
	}

	public function to_array() : array {
		return array_map(
			static fn( Action $action ) : array => $action->to_array(),
			$this->actions,
		);
	}

	public function getIterator() : ArrayIterator {
		return new ArrayIterator( $this->actions );
	}
}
