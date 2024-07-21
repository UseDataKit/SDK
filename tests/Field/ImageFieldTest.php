<?php

namespace DataKit\DataViews\Tests\Field;

use DataKit\DataViews\Field\ImageField;

/**
 * Unit tests for {@see ImageField}
 *
 * @since $ver$
 */
final class ImageFieldTest extends AbstractFieldTestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static function fieldClass(): string {
		return ImageField::class;
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_get_value(): void {
		$data  = [ 'image' => 'https://datakit.org/logo.png' ];
		$field = ImageField::create( 'image', 'Image' );

		self::assertSame( '<img src="https://datakit.org/logo.png" />', $field->get_value( $data ) );
		self::assertSame(
			'<img class="round" src="https://datakit.org/logo.png" />',
			$field->class( 'round' )->get_value( $data ),
		);

		self::assertSame(
			'<img class="round" alt="some alt text" src="https://datakit.org/logo.png" />',
			$field->class( 'round' )->alt( 'some alt text' )->get_value( $data ),
		);
		self::assertSame(
			'<img width="100" src="https://datakit.org/logo.png" />',
			$field->size( 100 )->get_value( $data ),
		);
		self::assertSame(
			'<img width="100" height="50" src="https://datakit.org/logo.png" />',
			$field->size( 100, 50 )->get_value( $data ),
		);

		self::assertEmpty( $field->get_value( [] ) );
	}
}
