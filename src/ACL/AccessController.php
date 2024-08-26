<?php

namespace DataKit\DataViews\ACL;

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
	 * @param mixed      ...$context The available context for the test.
	 *
	 * @return bool Whether the user has the capability.
	 */
	public function can( Capability $capability, ...$context ): bool;
}
