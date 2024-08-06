<?php

namespace DataKit\DataViews\Data;

use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Search;
use DataKit\DataViews\DataView\Sort;

/**
 * Abstract data source that provides common filtering and sorting methods.
 *
 * Note: methods are implemented to create an immutable data source.
 *
 * @since $ver$
 */
abstract class BaseDataSource implements DataSource {
	/**
	 * The filters to use.
	 *
	 * @since $ver$
	 *
	 * @var Filters|null
	 */
	protected ?Filters $filters = null;

	/**
	 * The sorting to use.
	 *
	 * @since $ver$
	 *
	 * @var Sort|null
	 */
	protected ?Sort $sort = null;

	/**
	 * The query to search by.
	 *
	 * @since $ver$
	 *
	 * @var Search|null
	 */
	protected ?Search $search = null;

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 *
	 * @return static
	 */
	public function filter_by( ?Filters $filters ) {
		$clone          = clone $this;
		$clone->filters = $filters;

		return $clone;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 * @return static
	 */
	public function sort_by( ?Sort $sort ) {
		$clone       = clone $this;
		$clone->sort = $sort;

		return $clone;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 * @return static
	 */
	public function search_by( ?Search $search ) {
		$clone         = clone $this;
		$clone->search = $search;

		return $clone;
	}
}
