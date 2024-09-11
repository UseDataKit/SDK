<?php

namespace DataKit\DataViews\Tests\Data\Exception;

use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\Data\Exception\DataNotFoundException;
use DataKit\DataViews\Translation\ReplaceParameters;
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
			use ReplaceParameters;

			public function translate( string $message, array $parameters = [] ): string {
				return $this->replace_parameters( $message, $parameters );
			}
		};

		$data_source    = new ArrayDataSource( 'test', [] );
		$custom_message = new DataNotFoundException( $data_source, 'some message' );
		$with_id        = DataNotFoundException::with_id( $data_source, 'test-id' );

		self::assertSame( $data_source, $custom_message->data_source() );
		self::assertSame( $data_source, $with_id->data_source() );

		self::assertSame( 'some message', $custom_message->translate( $translator ) );
		self::assertSame( 'datakit.data.not_found.with_id', $with_id->translate( $translator ) );
	}
}
