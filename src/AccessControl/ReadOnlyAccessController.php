<?php

namespace DataKit\DataViews\AccessControl;

use DataKit\DataViews\AccessControl\Capability\Capability;

/**
 * AccessControlManager that allows read access to anyone.
 *
 * @since $ver$
 */
final class ReadOnlyAccessController implements AccessController {
	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public function can( Capability $capability ): bool {
		return ! $capability->is_mutative() && ! $capability->is_destructive();
	}
}
