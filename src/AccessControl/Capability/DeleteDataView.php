<?php

namespace DataKit\DataViews\AccessControl\Capability;

/**
 * A capability representing a user can delete a DataView.
 *
 * @since $ver$
 */
final class DeleteDataView extends DataViewCapability {
	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public function is_destructive(): bool {
		return true;
	}
}
