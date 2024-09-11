<?php

namespace DataKit\DataViews\Tests\Data\Exception;

use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\Data\Exception\ActionForbiddenException;
use DataKit\DataViews\Translation\ReplaceParameters;
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
			use ReplaceParameters;

			public function translate( string $message, array $parameters = [] ): string {
				return $this->replace_parameters( $message, $parameters );
			}
		};

		$data_source    = new ArrayDataSource( 'test', [] );
		$custom_message = new ActionForbiddenException( $data_source, 'some message' );
		$with_id        = ActionForbiddenException::with_id( $data_source, 'test-id' );

		self::assertSame( $data_source, $custom_message->data_source() );
		self::assertSame( $data_source, $with_id->data_source() );

		self::assertSame( 'some message', $custom_message->translate( $translator ) );
		self::assertSame( 'datakit.action.forbidden.with_id', $with_id->translate( $translator ) );
	}
}
