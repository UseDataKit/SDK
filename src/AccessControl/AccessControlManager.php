<?php

namespace DataKit\DataViews\AccessControl;

use SplStack;

/**
 * Manages the current and previously set Access Controllers.
 *
 * By default, the manager will return a {@see ReadOnlyAccessController}.
 *
 * @since $ver$
 */
final class AccessControlManager {
	/**
	 * The current stack of Access Control Managers.
	 *
	 * @since $ver$
	 *
	 * @var SplStack<AccessController>
	 */
	private static \SplStack $access_controllers;

	/**
	 * Prevent creating multiple instances of the manager.
	 *
	 * @since $ver$
	 */
	private function __construct() {
	}

	/**
	 * Lazily initializes the Access Control Manager.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	private static function initialize(): void {
		if ( ! isset( self::$access_controllers ) ) {
			self::$access_controllers = new \SplStack();
			self::set( new ReadOnlyAccessController() );
		}
	}

	/**
	 * Sets the current AccessController.
	 *
	 * @since $ver$
	 *
	 * @param AccessController $access_controller The access controller.
	 */
	public static function set( AccessController $access_controller ): void {
		self::initialize();

		self::$access_controllers->push( $access_controller );
	}

	/**
	 * Returns the current Access Controller.
	 *
	 * @since $ver$
	 *
	 * @return AccessController The current AccessController.
	 */
	public static function current(): AccessController {
		self::initialize();

		return self::$access_controllers->top();
	}

	/**
	 * Resets the current Access Controller to the previous.
	 *
	 * @since $ver$
	 */
	public static function reset(): void {
		self::initialize();

		if ( self::$access_controllers->count() > 1 ) {
			self::$access_controllers->pop();
		}
	}
}
