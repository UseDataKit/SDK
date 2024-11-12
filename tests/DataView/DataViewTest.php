<?php

namespace DataKit\DataViews\Tests\DataView;

use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\DataView\Action;
use DataKit\DataViews\DataView\Actions;
use DataKit\DataViews\DataView\DataView;
use DataKit\DataViews\Field\EnumField;
use DataKit\DataViews\Field\ImageField;
use DataKit\DataViews\Field\TextField;
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
	public function test_to_js(): void {
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
"render": ( data ) => datakit_fields.html("$uuid", data, []),
TEXT;

		self::assertStringContainsString( $expected, $view->to_js( true ) );
	}

	/**
	 * Test case for {@see DataView::media_field()} and {@see DataView::primary_field()}.
	 *
	 * @since $ver$
	 */
	public function test_primary_and_media_fields(): void {
		$data_source = new ArrayDataSource( 'array', [] );
		$fields      = [
			$primary_field = TextField::create( 'primary', 'Primary' ),
			$image_field = ImageField::create( 'image', 'Normal image field' ),
			$media_field = ImageField::create( 'media', 'Media field' ),
		];

		$table   = DataView::table( 'table', $data_source, $fields )->media_field( $media_field );
		$table_2 = DataView::table( 'table-2', $data_source, $fields )->primary_field( $primary_field );
		$grid    = DataView::grid( 'grid', $data_source, $fields );
		$grid_2  = DataView::grid( 'grid-2', $data_source, $fields )
			->primary_field( $primary_field )
			->media_field( $media_field );

		self::assertArrayNotHasKey( 'primaryField', $table->to_array()['view']['layout'] );
		self::assertArrayNotHasKey( 'mediaField', $table->to_array()['view']['layout'] );
		self::assertArrayHasKey( 'primaryField', $table_2->to_array()['view']['layout'] );

		self::assertArrayNotHasKey( 'primaryField', $grid->to_array()['view']['layout'] );
		self::assertArrayHasKey( 'primaryField', $grid_2->to_array()['view']['layout'] );

		self::assertSame( $image_field->uuid(), $grid->to_array()['view']['layout']['mediaField'] );
		self::assertSame( $media_field->uuid(), $grid_2->to_array()['view']['layout']['mediaField'] );
	}

	/**
	 * Test case for {@see DataView::viewable()} and {@see DataView::deletable()}.
	 *
	 * @since $ver$
	 */
	public function testViewableDeletable(): void {
		$data_source = new ArrayDataSource( 'array', [] );
		$fields      = [
			TextField::create( 'primary', 'Primary' ),
		];

		$empty        = DataView::table( 'empty', $data_source, $fields );
		$with_actions = DataView::table( 'empty', $data_source, $fields );
		$with_actions->actions( Actions::of( Action::url( 'action', 'Label', 'url' ) ) );

		$empty->viewable( $fields )->deletable();
		$with_actions->viewable( $fields )->deletable();

		$empty->actions( static function ( Actions $actions ): Actions {
			self::assertCount( 2, $actions );
			self::assertSame( [ 'view', 'delete' ], array_keys( $actions->to_array() ) );

			return $actions;
		} );

		$with_actions->actions( static function ( Actions $actions ): Actions {
			self::assertCount( 3, $actions );
			self::assertSame( [ 'view', 'action', 'delete' ], array_keys( $actions->to_array() ) );

			return $actions;
		} );
	}
}
