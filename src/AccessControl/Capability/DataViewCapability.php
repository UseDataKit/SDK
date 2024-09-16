<?php

namespace DataKit\DataViews\AccessControl\Capability;

use DataKit\DataViews\DataView\DataView;

/**
 * Represents a capability that is connected to a DataView.
 *
 * @since $ver$
 */
abstract class DataViewCapability implements Capability {
	/**
	 * The DataView.
	 *
	 * @since $ver$
	 *
	 * @var DataView
	 */
	protected DataView $dataview;

	/**
	 * Creates the Capability.
	 *
	 * @since $ver$
	 */
	public function __construct( DataView $dataview ) {
		$this->dataview = $dataview;
	}

	/**
	 * Returns the DataView connected to the capability.
	 *
	 * @since $ver$
	 */
	public function dataview(): DataView {
		return $this->dataview;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public function is_mutative(): bool {
		return false;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public function is_destructive(): bool {
		return false;
	}
}
