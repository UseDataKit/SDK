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
	protected static function fieldClass(): string {
		return LinkField::class;
	}

	/**
	 * Test case for {@see LinkField::get_value()}.
	 *
	 * @since $ver$
	 */
	public function test_get_value(): void {
		$data = [
			'link' => 'https://datakit.org',
			'name' => 'DataKit',
		];

		$field_none = LinkField::create( 'link', 'Link' );
		self::assertSame(
			'<a href="https://datakit.org" target="_blank">https://datakit.org</a>',
			$field_none->get_value( $data ),
		);
		self::assertSame(
			'<a href="https://datakit.org" target="_self">https://datakit.org</a>',
			$field_none->on_same_window()->get_value( $data ),
		);

		self::assertSame(
			'<a href="https://datakit.org" target="_blank">Custom Label</a>',
			$field_none->with_label( 'Custom Label' )->get_value( $data ),
		);

		self::assertSame(
			'<a href="https://datakit.org" target="_blank">https://datakit.org</a>',
			$field_none->with_label( 'Custom Label' )->without_label()->get_value( $data ),
		);

		$field_field = LinkField::create( 'name', 'Link' )->link_to_field( 'link' )->on_same_window();
		self::assertSame(
			'<a href="https://datakit.org" target="_self">DataKit</a>',
			$field_field->get_value( $data ),
		);
		self::assertSame(
			'<a href="https://datakit.org" target="_blank">DataKit</a>',
			$field_field->on_new_window()->get_value( $data ),
		);

		self::assertEmpty( $field_field->get_value( [] ) );
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_callback(): void {
		$link = LinkField::create( 'id', 'Edit Submission' )
			->callback(
				fn( string $field, array $entry ) => sprintf( 'https://mysite.test/edit/%d', $entry[ $field ] ?? '' ),
			)
			->with_label( 'Edit Submission #{id}' );

		self::assertSame(
			'<a href="https://mysite.test/edit/123" target="_blank">Edit Submission #123</a>',
			$link->get_value( [ 'id' => '123' ] ),
		);
	}
}
