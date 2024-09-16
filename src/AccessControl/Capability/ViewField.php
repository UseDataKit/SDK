<?php

namespace DataKit\DataViews\AccessControl\Capability;

use DataKit\DataViews\DataView\DataView;
use DataKit\DataViews\Field\Field;

/**
 * A capability representing a user can view a DataView field.
 *
 * @since $ver$
 */
final class ViewField extends DataViewCapability {
	/**
	 * The Field.
	 *
	 * @since $ver$
	 *
	 * @var Field
	 */
	private Field $field;

	/**
	 * Creates the capability.
	 *
	 * @since $ver$
	 *
	 * @param DataView $dataview The DataView.
	 * @param Field    $field    The Field.
	 */
	public function __construct( DataView $dataview, Field $field ) {
		parent::__construct( $dataview );

		$this->field = $field;
	}

	/**
	 * Returns the Field to view.
	 *
	 * @since $ver$
	 *
	 * @return Field The Field.
	 */
	public function field(): Field {
		return $this->field;
	}
}
