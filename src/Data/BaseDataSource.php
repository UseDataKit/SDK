<?php

namespace DataKit\DataView\Data;

use DataKit\DataView\DataView\Filter;
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
	 * @var Filter[]|Filters|null
	 */
	protected ?Filters $filters = null;

	/**
	 * The sorting to use.
	 * @since $ver$
	 */
	protected ?Sort $sort = null;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function filter_by( ?Filters $filters ) : static {
		$clone          = clone $this;
		$clone->filters = $filters;

		return $clone;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function sort_by( ?Sort $sort ) : static {
		$clone       = clone $this;
		$clone->sort = $sort;

		return $clone;
	}
}
