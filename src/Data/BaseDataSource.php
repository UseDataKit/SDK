<?php

namespace DataKit\DataView\Data;

use DataKit\DataView\DataView\Filters;
use DataKit\DataView\DataView\Sort;

/**
 * Abstract data source that implements default methods.
 * @since $ver$
 */
abstract class BaseDataSource implements DataSource {
	/**
	 * The filters to use.
	 * @since $ver$
	 * @var Filters|null
	 */
	protected ?Filters $filters = null;

	/**
	 * The sorting to use.
	 * @since $ver$
	 */
	protected ?Sort $sort = null;

	/**
	 * The string to search by.
	 * @since $ver$
	 */
	protected string $search = '';

	/**
	 * @inheritDoc
	 * @since $ver$
	 * @return static
	 */
	public function filter_by( ?Filters $filters ) {
		$clone          = clone $this;
		$clone->filters = $filters;

		return $clone;
	}

	/**
	 * @inheritDoc
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
	 * @since $ver$
	 */
	public function search_by( string $search ) {
		$clone         = clone $this;
		$clone->search = $search;

		return $clone;
	}
}
