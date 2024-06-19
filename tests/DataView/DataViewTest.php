<?php

namespace DataKit\DataView\Tests\DataView;

use DataKit\DataView\Data\ArrayDataSource;
use DataKit\DataView\DataView\DataView;
use DataKit\DataView\Field\EnumField;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see DataView}
 * @since $ver$
 */
final class DataViewTest extends TestCase {
	/**
	 * Test case for {@see DataView::to_js()}.
	 * @since $ver$
	 */
	public function test_to_js() : void {
		$view = DataView::table(
			'test',
			[
				EnumField::create( 'test', 'Test', [ 'test' => 'Tes"\'t' ] ),
			],
			new ArrayDataSource( 'test', 'Test', [
				'test' => [ 'test' => 'Test' ]
			] )
		);

		$expected = <<<TEXT
"render":( data ) => datakit_fields.enum("test", data, {"elements":[{"label":"Tes\"'t","value":"test"}]}),
TEXT;

		self::assertStringContainsString( $expected, $view->to_js() );
	}
}
