<?php

namespace DataKit\DataViews\Tests\DataView;

use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\DataView\DataView;
use DataKit\DataViews\Field\EnumField;
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
				$field = EnumField::create( 'test', 'Test', [ 'test' => 'Tes"\'t' ] ),
			],
			new ArrayDataSource( 'test', 'Test', [
				'test' => [ 'test' => 'Test' ]
			] )
		);

		$uuid     = $field->uuid();
		$expected = <<<TEXT
"render":( data ) => datakit_fields.enum("$uuid", data, {"elements":[{"label":"Tes\"'t","value":"test"}]}),
TEXT;

		self::assertStringContainsString( $expected, $view->to_js() );
	}
}
