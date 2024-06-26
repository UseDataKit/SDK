<?php

namespace DataKit\DataViews\Tests\Field;

use DataKit\DataViews\Field\TextField;

final class TextFieldTest extends AbstractFieldTestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static function fieldClass() : string {
		return TextField::class;
	}
}
