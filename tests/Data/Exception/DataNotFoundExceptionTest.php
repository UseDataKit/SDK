<?php

namespace DataKit\DataViews\Tests\Data\Exception;

use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\Data\Exception\DataNotFoundException;
use DataKit\DataViews\Translation\NoopTranslator;
use DataKit\DataViews\Translation\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see DataNotFoundException}
 *
 * @since $ver$
 */
final class DataNotFoundExceptionTest extends TestCase {
	/**
	 * Test case for {@see DataNotFoundException}.
	 *
	 * @since $ver$
	 */
	public function test_exception(): void {
		$translator = new class implements Translator {
			public function translate( string $message, ...$values ): string {
				return sprintf( $message, ...$values );
			}
		};

		$data_source    = new ArrayDataSource( 'test', [] );
		$custom_message = new DataNotFoundException( $data_source, 'some message' );
		$with_id        = DataNotFoundException::with_id( $data_source, 'test-id' );

		self::assertSame( $data_source, $custom_message->data_source() );
		self::assertSame( $data_source, $with_id->data_source() );

		self::assertSame( 'some message', $custom_message->translate( $translator ) );
		self::assertSame(
			'Data set with id "test-id" not found.',
			$with_id->translate( $translator )
		);
	}
}
