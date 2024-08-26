<?php

namespace DataKit\DataViews\ACL;

use DataKit\DataViews\EnumObject;

/**
 * Represents a capability a user can have.
 *
 * @since $ver$
 *
 * @method static self view_dataview() Whether the user can view a DataView.
 * @method static self edit_dataview() Whether the user can edit a DataView.
 * @method static self view_dataview_field() Whether the user can view a DataView field.
 */
final class Capability extends EnumObject {
	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	protected static function cases(): array {
		return [
			'view_dataview'       => 'view_dataview',
			'edit_dataview'       => 'edit_dataview',
			'view_dataview_field' => 'view_dataview_field',
		];
	}
}
