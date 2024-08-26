<?php

namespace DataKit\DataViews\Tests\ACL;

use DataKit\DataViews\ACL\Capability;
use DataKit\DataViews\ACL\ReadOnlyAccessController;
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

		self::assertTrue( $controller->can( Capability::view_dataview() ) );
		self::assertTrue( $controller->can( Capability::view_dataview_field() ) );
		self::assertFalse( $controller->can( Capability::edit_dataview() ) );
	}
}
