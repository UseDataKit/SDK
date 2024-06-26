<?php

namespace DataKit\DataViews\Tests\Field;

use DataKit\DataViews\Field\LinkField;

final class LinkFieldTest extends AbstractFieldTestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static function fieldClass() : string {
		return LinkField::class;
	}
}
