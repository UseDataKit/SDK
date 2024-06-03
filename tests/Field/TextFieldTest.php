<?php

namespace DataKit\DataView\Tests\Field;

use DataKit\DataView\Field\TextField;

final class TextFieldTest extends AbstractFieldTestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static function fieldClass() : string {
		return TextField::class;
	}
}
