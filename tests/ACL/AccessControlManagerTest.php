<?php

namespace DataKit\DataViews\Tests\ACL;

use DataKit\DataViews\ACL\AccessController;
use DataKit\DataViews\ACL\AccessControlManager;
use DataKit\DataViews\ACL\Capability;
use DataKit\DataViews\ACL\ReadOnlyAccessController;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see AccessControlManager}
 *
 * @since $ver$
 */
final class AccessControlManagerTest extends TestCase {
	/**
	 * Creates an in-memory access controller instance.
	 *
	 * @since $ver$
	 *
	 * @return AccessController
	 */
	private static function create_access_controller(): AccessController {
		return new class implements AccessController {
			public function can( Capability $capability, ...$context ): bool {
				return false;
			}
		};
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_manager(): void {
		self::assertInstanceOf( ReadOnlyAccessController::class, AccessControlManager::current() );

		$fake   = self::create_access_controller();
		$fake_2 = self::create_access_controller();

		AccessControlManager::set( $fake );
		AccessControlManager::set( $fake_2 );

		self::assertNotInstanceOf( ReadOnlyAccessController::class, AccessControlManager::current() );
		self::assertSame( $fake_2, AccessControlManager::current() );

		AccessControlManager::reset();
		self::assertSame( $fake, AccessControlManager::current() );

		AccessControlManager::reset();
		AccessControlManager::reset(); // Can't reset beyond all access.

		self::assertInstanceOf( ReadOnlyAccessController::class, AccessControlManager::current() );
	}
}
