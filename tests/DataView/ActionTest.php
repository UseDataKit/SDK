<?php

namespace DataKit\DataViews\Tests\DataView;

use DataKit\DataViews\DataView\Action;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Action}
 *
 * @since $ver$
 */
final class ActionTest extends TestCase {
	/**
	 * Test case for {@see Action::url()}.
	 *
	 * @since $ver$
	 */
	public function test_url(): void {
		$action = Action::url( 'some-action', 'Action', 'url' );
		$array  = $action->to_array();

		self::assertStringContainsString( 'datakit_dataviews_actions.url', $array['callback'] ?? '' );
		self::assertStringContainsString( '"type":"url"', $array['callback'] ?? '' );
		self::assertSame( 'some-action', $array['id'] );
		self::assertSame( 'Action', $array['label'] );
	}

	/**
	 * Test case for {@see Action::modal()}.
	 *
	 * @since $ver$
	 */
	public function test_modal(): void {
		$modal = Action::modal( 'modal-action', 'Modal Action', 'some-url', true );
		$array = $modal->to_array();
		self::assertNull( $array['callback'] ?? null );
		self::assertSame( 'modal-action', $array['id'] );
		self::assertSame( 'Modal Action', $array['label'] );

		$modal_callback = $array['RenderModal'] ?? '';
		self::assertStringContainsString( 'datakit_modal(', $modal_callback );
		self::assertStringContainsString( '{"url":"some-url","id":"modal-action","type":"modal"}', $modal_callback );
	}

	/**
	 * Test case for {@see Action::ajax()}.
	 *
	 * @since $ver$
	 */
	public function test_ajax(): void {
		$ajax = Action::ajax( 'ajax-action',
			'Ajax Action',
			'some-url',
			'PUT',
			[ 'extra' => 'param' ],
			true );
		$put  = $ajax->to_array();
		$ajax = Action::ajax( 'ajax-action', 'Ajax Action', 'some-url', 'POST', [] );
		$post = $ajax->to_array();

		self::assertStringContainsString( 'datakit_dataviews_actions.url', $put['callback'] ?? '' );
		self::assertStringContainsString( '"type":"ajax"', $put['callback'] ?? '' );
		self::assertStringContainsString( '"method":"PUT"', $put['callback'] ?? '' );
		self::assertStringContainsString( '"params":{"extra":"param"}', $put['callback'] ?? '' );
		self::assertStringContainsString( '"use_single_request":true', $put['callback'] ?? '' );
		self::assertSame( 'ajax-action', $put['id'] );
		self::assertSame( 'Ajax Action', $put['label'] );

		self::assertStringContainsString( '"method":"POST"', $post['callback'] ?? '' );
		self::assertStringContainsString( '"params":[]', $post['callback'] ?? '' );
		self::assertStringContainsString( '"use_single_request":false', $post['callback'] ?? '' );
		self::assertSame( 'ajax-action', $post['id'] );
		self::assertSame( 'Ajax Action', $post['label'] );
	}

	/**
	 * Test case for the basic action modifiers.
	 *
	 * @since $ver$
	 */
	public function test_modifiers(): void {
		$action                   = Action::url( 'some-action', 'Action', 'url' );
		$destructive_primary_bulk = $action->destructive()->primary( 'action-icon' )->bulk();

		self::assertFalse( $action->to_array()['isDestructive'] );
		self::assertFalse( $action->to_array()['isPrimary'] );
		self::assertFalse( $action->to_array()['supportsBulk'] );
		self::assertEmpty( $action->to_array()['icon'] );

		self::assertTrue( $destructive_primary_bulk->to_array()['isDestructive'] );
		self::assertTrue( $destructive_primary_bulk->to_array()['isPrimary'] );
		self::assertTrue( $destructive_primary_bulk->to_array()['supportsBulk'] );
		self::assertSame( 'action-icon', $destructive_primary_bulk->to_array()['icon'] );

		$normal_secondary_single = $destructive_primary_bulk->not_destructive()->single()->secondary();

		self::assertFalse( $normal_secondary_single->to_array()['isDestructive'] );
		self::assertFalse( $normal_secondary_single->to_array()['isPrimary'] );
		self::assertFalse( $normal_secondary_single->to_array()['supportsBulk'] );
		self::assertEmpty( $normal_secondary_single->to_array()['icon'] );
	}

	/**
	 * Test case for {@see Action::requires()}.
	 *
	 * @since $ver$
	 */
	public function test_requires(): void {
		$action = Action::url( 'some-action', 'Action', 'url' )->requires( [ 'url', 'other' ] );
		$empty  = $action->requires( [] );
		self::assertStringContainsString( '["url","other"].every(', $action->to_array()['isEligible'] );
		self::assertNull( $empty->to_array()['isEligible'] );
	}

	/**
	 * Test case for {@see Action::confirm()}.
	 *
	 * @since $ver$
	 */
	public function test_confirm(): void {
		$action  = Action::url( 'some-action', 'Action', 'url' );
		$confirm = $action->confirm( 'sure?' );
		self::assertStringNotContainsString( 'confirm', $action->to_array()['callback'] );
		self::assertStringContainsString( '"confirm":"sure?"', $confirm->to_array()['callback'] );
	}

	/**
	 * Test case for {@see Action::allow_scripts()} and {@see Action::deny_scripts()}.
	 *
	 * @since $ver$
	 */
	public function test_scripts(): void {
		$allowed = Action::url( 'some-action', 'Action', 'url' )->allow_scripts();
		$denied  = Action::url( 'some-action', 'Action', 'url' )->deny_scripts();
		self::assertStringContainsString( '"is_scripts_allowed":true', $allowed->to_array()['callback'] );
		self::assertStringContainsString( '"is_scripts_allowed":false', $denied->to_array()['callback'] );
	}
}
