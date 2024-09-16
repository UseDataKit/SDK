<?php

namespace DataKit\DataViews\AccessControl\Capability;

/**
 * Represents a capability a user can have.
 *
 * @since $ver$
 */
interface Capability {
	/**
	 * Returns whether the capability is related to mutation.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether the capability is related to mutation.
	 */
	public function is_mutative(): bool;

	/**
	 * Returns whether the capability is related to destruction.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether the capability is related to destruction.
	 */
	public function is_destructive(): bool;
}
