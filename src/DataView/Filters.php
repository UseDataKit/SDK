<?php

namespace DataKit\DataViews\DataView;

use ArrayIterator;
use DataKit\DataViews\Field\Field;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Represents a collection of fields.
 * @since $ver$
 */
final class Filters implements IteratorAggregate {
	/**
	 * The Filters.
	 * @var Filter[]
	 */
	private array $filters;

	/**
	 * Creates the collection.
	 * @since $ver$
	 *
	 * @param Filter ...$filters The filters.
	 */
	private function __construct( Filter ...$filters ) {
		$this->filters = $filters;
	}

	/**
	 * Creates collection of filters.
	 * @since $ver$
	 *
	 * @param Filter ...$filters The filters.
	 *
	 * @return self The filter collection.
	 */
	public static function of( Filter ...$filters ) : self {
		if ( ! $filters ) {
			throw new InvalidArgumentException( 'No filters provided.' );
		}

		return new self( ...$filters );
	}

	/**
	 * Creates collection of filters from an array.
	 * @since $ver$
	 *
	 * @param array $array The array of filters.
	 *
	 * @return self The filter collection.
	 */
	public static function from_array( array $array ) : self {
		$filters = [];
		foreach ( $array as $filter ) {
			$filters[] = Filter::from_array( $filter );
		}

		return new self( ... $filters );
	}

	/**
	 * Serializes the collection to an array.
	 * @since $ver$
	 * @return array[array{field: string, operator: string, value: string}] The fields as an array.
	 */
	public function to_array() : array {
		return array_map(
			static fn( Filter $filter ) : array => $filter->to_array(),
			$this->filters
		);
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 * @return ArrayIterator|Field[] The field iterator.
	 */
	public function getIterator() : ArrayIterator {
		return new ArrayIterator( $this->filters );
	}

	/**
	 * Returns whether the entry matches the filters.
	 * @since $ver$
	 *
	 * @param array $data The data to match against.
	 *
	 * @return bool whether the entry matches the filters.
	 */
	public function match( array $data ) : bool {
		foreach ( $this->filters as $filter ) {
			if ( ! $filter->matches( $data ) ) {
				return false;
			}
		}

		return true;
	}
}
