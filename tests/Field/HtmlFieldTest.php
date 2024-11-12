<?php

namespace DataKit\DataViews\Tests\Field;

use DataKit\DataViews\Field\HtmlField;

/**
 * Unit tests for {@see HtmlField}
 *
 * @since $ver$
 *
 * @extends AbstractFieldTestCase<HtmlField>
 */
final class HtmlFieldTest extends AbstractFieldTestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static function fieldClass(): string {
		return HtmlField::class;
	}

	/**
	 * Test case for {@see HtmlField::allow_scripts()} and {@see HtmlField::deny_scripts()}.
	 *
	 * @since $ver$
	 */
	public function test_scripts(): void {
		$field   = $this->createField( 'html', 'Html Field' );
		$allowed = $field->allow_scripts();
		$denied  = $allowed->deny_scripts();

		self::assertStringContainsString( '{"is_scripts_allowed":false}', $field->to_array()['render'] );
		self::assertStringContainsString( '{"is_scripts_allowed":false}', $denied->to_array()['render'] );
		self::assertStringContainsString( '{"is_scripts_allowed":true}', $allowed->to_array()['render'] );
	}
}
