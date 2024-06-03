<?php

namespace DataKit\DataView\Tests\Field;

use DataKit\DataView\Field\LinkField;

final class LinkFieldTest extends AbstractFieldTestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static function fieldClass() : string {
		return LinkField::class;
	}
}
