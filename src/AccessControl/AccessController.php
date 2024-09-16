<?php

namespace DataKit\DataViews\AccessControl;

use DataKit\DataViews\AccessControl\Capability\Capability;
use DataKit\DataViews\DataView\DataView;
use DataKit\DataViews\Field\Field;

/**
 * Manages the access the current user has on {@see DataView} and {@see Field} objects.
 *
 * @since $ver$
 */
interface AccessController {
	/**
	 * Returns whether the user has the provided capability.
	 *
	 * @since $ver$
	 *
	 * @param Capability $capability The capability to test.
	 *
	 * @return bool Whether the user has the capability.
	 */
	public function can( Capability $capability ): bool;
}
