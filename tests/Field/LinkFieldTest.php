<?php

namespace DataKit\DataViews\Tests\Field;

use DataKit\DataViews\Field\LinkField;

/**
 * Unit tests for {@see LinkField}.
 *
 * @since $ver$
 */
final class LinkFieldTest extends AbstractFieldTestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static function fieldClass() : string {
		return LinkField::class;
	}

	/**
	 * Test case for {@see LinkField::get_value()}.
	 *
	 * @since $ver$
	 */
	public function test_get_value() : void {
		$data = [
			'link' => 'https://datakit.org',
			'name' => 'DataKit',
		];

		$field = LinkField::create( 'link', 'Link' );
		self::assertSame(
			'<a href="https://datakit.org" target="_blank">https://datakit.org</a>',
			$field->get_value( $data ),
		);
		self::assertSame(
			'<a href="https://datakit.org" target="_self">https://datakit.org</a>',
			$field->on_same_window()->get_value( $data ),
		);

		$field = LinkField::create( 'name', 'Link' )->linkToField( 'link' )->on_same_window();
		self::assertSame(
			'<a href="https://datakit.org" target="_self">DataKit</a>',
			$field->get_value( $data ),
		);
		self::assertSame(
			'<a href="https://datakit.org" target="_blank">DataKit</a>',
			$field->on_new_window()->get_value( $data ),
		);
	}
}
