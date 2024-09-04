<?php

namespace DataKit\DataViews\Tests\Data\Exception;

use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\Data\Exception\ActionForbiddenException;
use DataKit\DataViews\Translation\NoopTranslator;
use DataKit\DataViews\Translation\Translator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ActionForbiddenException}
 *
 * @since $ver$
 */
final class ActionForbiddenExceptionTest extends TestCase {
	/**
	 * Test case for {@see ActionForbiddenException}.
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
		$custom_message = new ActionForbiddenException( $data_source, 'some message' );
		$with_id        = ActionForbiddenException::with_id( $data_source, 'test-id' );

		self::assertSame( $data_source, $custom_message->data_source() );
		self::assertSame( $data_source, $with_id->data_source() );

		self::assertSame( 'some message', $custom_message->translate( $translator ) );
		self::assertSame(
			'This action is forbidden for data set with id "test-id".',
			$with_id->translate( $translator )
		);
	}
}
