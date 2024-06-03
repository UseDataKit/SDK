<?php

namespace DataKit\DataView\DataView;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

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
	public function from_array( array $array ) : self {
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
	 */
	public function getIterator() : Traversable {
		return new ArrayIterator( $this->filters );
	}
}
