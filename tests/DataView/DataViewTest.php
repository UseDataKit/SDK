<?php

namespace DataKit\DataViews\Tests\DataView;

use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\DataView\DataView;
use DataKit\DataViews\Field\EnumField;
use DataKit\DataViews\Field\Field;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see DataView}
 *
 * @since $ver$
 */
final class DataViewTest extends TestCase {
	/**
	 * Test case for {@see DataView::to_js()}.
	 *
	 * @since $ver$
	 */
	public function test_to_js() : void {
		$view = DataView::table(
			'test',
			new ArrayDataSource(
				'test',
				[
					'test' => [ 'test' => 'Test' ],
				],
			),
			[
				$field = EnumField::create( 'test', 'Test', [ 'test' => 'Tes"\'t' ] ),
			],
		);

		$uuid     = $field->uuid();
		$expected = <<<TEXT
"render":( data ) => datakit_fields.html("$uuid", data, []),
TEXT;

		self::assertStringContainsString( $expected, $view->to_js() );
	}

	/**
	 * Test case for {@see DataView::primary_field()}.
	 *
	 * @since $ver$
	 */
	public function test_primary_field_appears_in_layout(): void {
		$field = EnumField::create( 'test', 'Test', [ 'v' => 'V' ] );

		$view = DataView::table(
			'test-primary',
			new ArrayDataSource(
				'test',
				[
					'row1' => [ 'test' => 'v' ],
				],
			),
			[ $field ],
		);

		$view->primary_field( $field );
		$data = $view->to_array();

		self::assertArrayHasKey( 'primaryField', $data['view']['layout'] );
		self::assertSame( $field->uuid(), $data['view']['layout']['primaryField'] );
	}

	/**
	 * Test that without primary_field, layout omits primaryField key.
	 *
	 * @since $ver$
	 */
	public function test_no_primary_field_by_default(): void {
		$view = DataView::table(
			'test-no-primary',
			new ArrayDataSource(
				'test',
				[
					'row1' => [ 'test' => 'v' ],
				],
			),
			[ EnumField::create( 'test', 'Test', [ 'v' => 'V' ] ) ],
		);

		$data = $view->to_array();

		self::assertArrayNotHasKey( 'primaryField', $data['view']['layout'] );
	}
}
