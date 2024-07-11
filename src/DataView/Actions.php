<?php

namespace DataKit\DataViews\DataView;

use ArrayIterator;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Represents a collection of actions.
 *
 * @link  https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/#actions-object
 *
 * @since $ver$
 */
final class Actions implements IteratorAggregate {
	/**
	 * The actions on the collection.
	 *
	 * @since $ver$
	 * @var Action[]
	 */
	private array $actions;

	/**
	 * Creates a collection.
	 *
	 * @since $ver$
	 *
	 * @param Action ...$actions The actions.
	 */
	private function __construct( Action ...$actions ) {
		if ( ! $actions ) {
			throw new InvalidArgumentException( 'No actions provided.' );
		}

		$this->actions = $actions;
	}

	/**
	 * Named constructor for the collection.
	 *
	 * @param Action ...$actions The actions.
	 *
	 * @return self The collection.
	 */
	public static function of( Action ...$actions ) : self {
		return new self( ...$actions );
	}

	/**
	 * Returns a serialized set of actions.
	 *
	 * @since $ver$
	 * @return array
	 */
	public function to_array() : array {
		return array_map(
			static fn( Action $action ) : array => $action->to_array(),
			$this->actions,
		);
	}

	/**
	 * Returns an iterator to loop over the collection.
	 *
	 * @since $ver$
	 * @return ArrayIterator The iterator.
	 */
	public function getIterator() : ArrayIterator {
		return new ArrayIterator( $this->actions );
	}
}
