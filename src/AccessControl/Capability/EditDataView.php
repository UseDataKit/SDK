<?php

namespace DataKit\DataViews\AccessControl\Capability;

/**
 * A capability representing a user can edit a DataView.
 *
 * @since $ver$
 */
final class EditDataView extends DataViewCapability {
	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public function is_mutative(): bool {
		return true;
	}
}
