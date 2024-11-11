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
	 *
	 * @var Action[]
	 */
	private array $actions = [];

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

		$this->add_actions( $actions );
	}

	/**
	 * Adds provided actions to the collection.
	 *
	 * @since $ver$
	 *
	 * @param iterable $actions      The action.
	 * @param bool     $is_prepended Whether the action should be prepended to the actions list.
	 */
	private function add_actions( iterable $actions, bool $is_prepended = false ): void {
		$list = [];
		foreach ( $actions as $action ) {
			if ( ! $action instanceof Action ) {
				continue;
			}
			$id          = $action->to_array()['id'];
			$list[ $id ] = $action;
		}

		if ( ! $is_prepended ) {
			$this->actions = array_merge( $this->actions, $list );
		} else {
			$this->actions = array_merge( $list, $this->actions );
		}
	}

	/**
	 * Named constructor for the collection.
	 *
	 * @param Action ...$actions The actions.
	 *
	 * @return self The collection.
	 */
	public static function of( Action ...$actions ): self {
		return new self( ...$actions );
	}

	/**
	 * Returns a collection with extra actions appended.
	 *
	 * @since $ver$
	 *
	 * @param Action ...$actions The actions.
	 *
	 * @return self the collection.
	 */
	public function append( Action ...$actions ): self {
		if ( ! $actions ) {
			throw new InvalidArgumentException( 'No actions provided.' );
		}

		$clone = clone $this;
		$clone->add_actions( $actions );

		return $clone;
	}

	/**
	 * Returns a collection with extra actions prepend.
	 *
	 * @since $ver$
	 *
	 * @param Action ...$actions The actions.
	 *
	 * @return self The collection.
	 */
	public function prepend( Action ...$actions ): self {
		if ( ! $actions ) {
			throw new InvalidArgumentException( 'No actions provided.' );
		}

		$clone = clone $this;
		$clone->add_actions( $actions, true );

		return $clone;
	}

	/**
	 * Returns a serialized set of actions.
	 *
	 * @since $ver$
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array_map(
			static fn( Action $action ): array => $action->to_array(),
			$this->actions,
		);
	}

	/**
	 * Returns an iterator to loop over the collection.
	 *
	 * @since $ver$
	 *
	 * @return ArrayIterator<Action> The iterator.
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator( $this->actions );
	}

	/**
	 * Merges two actions collections into a new collection.
	 *
	 * @since $ver$
	 *
	 * @param Actions $other The other actions collection.
	 *
	 * @return self The new collection.
	 */
	public function merge( Actions $other ): Actions {
		$clone = clone $this;
		$clone->add_actions( $other );

		return $clone;
	}

	/**
	 * Returns a new collection where the provided action IDs are excluded.
	 *
	 * @since $ver$
	 *
	 * @param string ...$action_ids The action IDs to exclude.
	 *
	 * @return self The new collection.
	 */
	public function without( string ...$action_ids ): self {
		if ( ! $action_ids ) {
			throw new InvalidArgumentException( 'No action IDs provided.' );
		}

		$clone          = clone $this;
		$clone->actions = array_diff_key( $clone->actions, array_flip( $action_ids ) );

		return $clone;
	}
}
