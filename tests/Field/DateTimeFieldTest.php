<?php

namespace DataKit\DataViews\Tests\Field;

use DataKit\DataViews\Field\DateTimeField;
use DateTimeZone;

/**
 * Unit tests for {@see DateTimeField}
 *
 * @since $ver$
 */
final class DateTimeFieldTest extends AbstractFieldTestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static function fieldClass() : string {
		return DateTimeField::class;
	}

	/**
	 * Test case for {@see DateTimeField::get_value()}.
	 *
	 * @since $ver$
	 */
	public function test_get_value() : void {
		$data      = [
			'utc'       => '2024-07-16 15:57:45',
			'amsterdam' => '12:00 on 01-04-2024',
		];
		$utc       = DateTimeField::create( 'utc', 'DateTime (UTC)' );
		$amsterdam = DateTimeField::create( 'amsterdam', 'DateTime (Amsterdam)' );

		self::assertSame( '2024-07-16 15:57:45', $utc->get_value( $data ) );
		self::assertSame(
			'Tue, 16 Jul 2024 15:57',
			$utc->to_format( 'D, d M Y H:i' )->get_value( $data ),
		);
		self::assertSame(
			'Tue, 16 Jul 2024 17:57', // GMT + 2
			$utc->to_format( 'D, d M Y H:i', new DateTimeZone( 'Europe/Amsterdam' ) )->get_value( $data ),
		);

		// Not parsed
		self::assertSame( '12:00 on 01-04-2024', $amsterdam->get_value( $data ) );
		self::assertSame(
			'2024-04-01T10:00:00+00:00',
			$amsterdam
				->from_format( 'H:i \o\n d-m-Y', new DateTimeZone( 'Europe/Amsterdam' ) )
				->to_format( 'c', new DateTimeZone( 'UTC' ) )
				->get_value( $data ),
		);
	}
}
