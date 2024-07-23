<?php

namespace DataKit\DataViews\DataView;

use ArrayIterator;
use IteratorAggregate;

/**
 * Represents a collection of fields.
 *
 * @since $ver$
 *
 * @phpstan-import-type FilterShape from Filter
 *
 * @implements IteratorAggregate<Filter>
 */
final class Filters implements IteratorAggregate {
	/**
	 * The Filters.
	 *
	 * @var Filter[]
	 */
	private array $filters;

	/**
	 * Creates the collection.
	 *
	 * @since $ver$
	 *
	 * @param Filter ...$filters More filters.
	 */
	private function __construct( Filter ...$filters ) {
		$this->filters = $filters;
	}

	/**
	 * Creates the collection of filters.
	 *
	 * @since $ver$
	 *
	 * @param Filter $filter     The primary filter.
	 * @param Filter ...$filters More filters.
	 *
	 * @return self The filter collection.
	 */
	public static function of( Filter $filter, Filter ...$filters ): self {
		return new self( $filter, ...$filters );
	}

	/**
	 * Creates the collection of filters from an array.
	 *
	 * @since $ver$
	 *
	 * @param array $filters_array The array of filters.
	 *
	 * @return self The filter collection.
	 */
	public static function from_array( array $filters_array ): self {
		$filters = [];

		foreach ( $filters_array as $filter ) {
			$filters[] = Filter::from_array( $filter );
		}

		return new self( ...$filters );
	}

	/**
	 * Serializes the collection to an array.
	 *
	 * @since $ver$
	 *
	 * @return array<FilterShape> The fields as an array.
	 */
	public function to_array(): array {
		return array_map(
			static fn( Filter $filter ): array => $filter->to_array(),
			$this->filters,
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function getIterator(): ArrayIterator {
		return new ArrayIterator( $this->filters );
	}

	/**
	 * Returns whether the entry matches the filters.
	 *
	 * @since $ver$
	 *
	 * @param array $data The data to match against.
	 *
	 * @return bool Whether the entry matches the filters.
	 */
	public function match( array $data ): bool {
		foreach ( $this->filters as $filter ) {
			if ( ! $filter->matches( $data ) ) {
				return false;
			}
		}

		return true;
	}
}
