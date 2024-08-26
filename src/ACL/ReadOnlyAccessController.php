<?php

namespace DataKit\DataViews\ACL;

/**
 * AccessControlManager that allows full access to anyone.
 *
 * @since $ver$
 */
final class ReadOnlyAccessController implements AccessController {
	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public function can( Capability $capability, ...$context ): bool {
		return strpos( $capability->as_string(), 'view_' ) === 0;
	}
}
