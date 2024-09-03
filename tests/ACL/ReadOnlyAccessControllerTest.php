<?php

namespace DataKit\DataViews\Tests\ACL;

use DataKit\DataViews\ACL\Capability\DeleteDataView;
use DataKit\DataViews\ACL\Capability\EditDataView;
use DataKit\DataViews\ACL\Capability\ViewDataView;
use DataKit\DataViews\ACL\Capability\ViewField;
use DataKit\DataViews\ACL\ReadOnlyAccessController;
use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\DataView\DataView;
use DataKit\DataViews\Field\TextField;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ReadOnlyAccessController}
 *
 * @since $ver$
 */
final class ReadOnlyAccessControllerTest extends TestCase {
	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_controller(): void {
		$controller = new ReadOnlyAccessController();

		$field    = TextField::create( 'test', 'Test' );
		$dataview = DataView::table( 'test', new ArrayDataSource( 'test', [] ), [ $field ] );

		self::assertTrue( $controller->can( new ViewDataView( $dataview ) ) );
		self::assertFalse( $controller->can( new EditDataView( $dataview ) ) );
		self::assertFalse( $controller->can( new DeleteDataView( $dataview ) ) );
		self::assertTrue( $controller->can( new ViewField( $dataview, $field ) ) );
	}
}
