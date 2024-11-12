<?php

namespace DataKit\DataViews\Tests\DataView;

use DataKit\DataViews\DataView\Action;
use DataKit\DataViews\DataView\Actions;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Actions}
 *
 * @since $ver$
 */
final class ActionsTest extends TestCase {
	/**
	 * Test case for {@see Actions::of()} and {@see Actions::to_array()}.
	 *
	 * @since $ver$
	 */
	public function testOf(): void {
		$actions = Actions::of(
			$action_1 = Action::url( 'action1', 'Action 1', 'https://some-url.test' )->primary( 'one' ),
			$action_2 = Action::url( 'action2', 'Action 2', 'https://some-url.test' ),
		);

		self::assertCount( 2, $actions );
		self::assertSame(
			[ 'action1' => $action_1->to_array(), 'action2' => $action_2->to_array() ],
			$actions->to_array()
		);

		$this->expectException( \InvalidArgumentException::class );
		Actions::of();
	}

	/**
	 * Test case for {@see Actions::getIterator()}.
	 *
	 * @since $ver$
	 */
	public function testGetIterator(): void {
		$actions = Actions::of(
			$action_1 = Action::url( 'action1', 'Action 1', 'https://some-url.test' )->primary( 'one' ),
			$action_2 = Action::url( 'action2', 'Action 2', 'https://some-url.test' ),
		);

		$result = iterator_to_array( $actions );

		self::assertSame( [ 'action1' => $action_1, 'action2' => $action_2 ], $result );
	}

	/**
	 * Test case for {@see Actions::append()} and {@see Actions::prepend()}.
	 *
	 * @since $ver$
	 */
	public function testAppendPrepend(): void {
		$actions = Actions::of(
			Action::url( 'action1', 'Action 1', 'https://some-url.test' )->primary( 'one' ),
			Action::url( 'action2', 'Action 2', 'https://some-url.test' ),
		);

		$append = $actions->append(
			Action::url( 'action3', 'Action 3', 'https://some-url.test' ),
			Action::url( 'action4', 'Action 4', 'https://some-url.test' ),
		);

		$prepend = $actions->prepend(
			Action::url( 'action3', 'Action 3', 'https://some-url.test' ),
			Action::url( 'action4', 'Action 4', 'https://some-url.test' ),
		);

		self::assertNotSame( $actions, $append );
		self::assertNotSame( $actions, $prepend );
		self::assertNotSame( $append, $prepend );

		self::assertCount( 2, $actions );
		self::assertCount( 4, $append );
		self::assertCount( 4, $prepend );

		self::assertSame( [ 'action3', 'action4', 'action1', 'action2' ], array_keys( $prepend->to_array() ) );
		self::assertSame( [ 'action1', 'action2', 'action3', 'action4' ], array_keys( $append->to_array() ) );
	}

	/**
	 * Test case for {@see Actions::append()} and {@see Actions::prepend()} without any params.
	 *
	 * @since $ver$
	 *
	 * @param bool $is_prepend Whether the method to be called is `prepend()`.
	 *
	 * @testWith [false]
	 *                [true]
	 */
	public function testAppendPrependException( bool $is_prepend ): void {
		$actions = Actions::of(
			Action::url( 'action1', 'Action 1', 'https://some-url.test' ),
		);

		$this->expectException( \InvalidArgumentException::class );

		$is_prepend
			? $actions->prepend()
			: $actions->append();
	}

	/**
	 * Test case for {@see Actions::merge()}.
	 *
	 * @since $ver$
	 */
	public function testMerge(): void {
		$actions = Actions::of(
			Action::url( 'action1', 'Action 1', 'https://some-url.test' ),
			Action::url( 'action2', 'Action 2', 'https://some-url.test' ),
		);

		$additional = Actions::of(
			Action::url( 'action3', 'Action 3', 'https://some-url.test', ),
			Action::url( 'action1', 'Overwritten action 1', 'https://overwritten.test' ),
			Action::url( 'action4', 'Action 4', 'https://some-url.test' ),
		);

		$merged          = $actions->merge( $additional );
		$merged_reversed = $additional->merge( $actions );

		self::assertNotSame( $merged, $actions );
		self::assertNotSame( $merged, $additional );
		self::assertCount( 2, $actions );
		self::assertCount( 3, $additional );
		self::assertCount( 4, $merged ); // Because action 1 is overwritten.
		self::assertCount( 4, $merged_reversed );

		self::assertSame( [ 'action1', 'action2', 'action3', 'action4' ], array_keys( $merged->to_array() ) );
		self::assertSame( [ 'action3', 'action1', 'action4', 'action2' ], array_keys( $merged_reversed->to_array() ) );

		self::assertSame( 'Overwritten action 1', $merged->to_array()['action1']['label'] );
		self::assertSame( 'Action 1', $merged_reversed->to_array()['action1']['label'] );
	}

	/**
	 * Test case for {@see Actions::without())}.
	 *
	 * @since $ver$
	 */
	public function testWithout(): void {
		$actions = Actions::of(
			Action::url( 'action1', 'Action 1', 'https://overwritten.test' ),
			Action::url( 'action2', 'Action 2', 'https://some-url.test', ),
			Action::url( 'action3', 'Action 3', 'https://some-url.test', ),
			Action::url( 'action4', 'Action 4', 'https://some-url.test' ),
		);

		$without = $actions->without( 'action2', 'action3' );

		self::assertNotSame( $without, $actions );
		self::assertCount( 2, $without );
		self::assertSame( [ 'action1', 'action4' ], array_keys( $without->to_array() ) );

		$this->expectException( \InvalidArgumentException::class );
		$actions->without();
	}
}
