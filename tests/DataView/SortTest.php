<?php

namespace DataKit\DataViews\Tests\DataView;

use DataKit\DataViews\DataView\Sort;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Sort}.
 *
 * @since $ver$
 */
final class SortTest extends TestCase {
	/**
	 * Test case for {@see Sort::asc()}.
	 *
	 * @since $ver$
	 */
	public function test_asc(): void {
		$sort  = Sort::asc( 'field_name' );
		$array = $sort->to_array();
		self::assertSame( Sort::ASC, $array['direction'] );
		self::assertSame( 'field_name', $array['field'] );
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_desc(): void {
		$sort  = Sort::desc( 'field_name' );
		$array = $sort->to_array();
		self::assertSame( Sort::DESC, $array['direction'] );
		self::assertSame( 'field_name', $array['field'] );
	}

	/**
	 * Test case for {@see Sort::from_array()}.
	 *
	 * @since $ver$
	 */
	public function test_from_array(): void {
		$sort = Sort::from_array( $array = [
			'field'     => 'field_key',
			'direction' => Sort::DESC,
		] );
		self::assertSame( $array, $sort->to_array() );
	}

	/**
	 * Test case for {@see Sort::from_array() with invalid data.
	 *
	 * @since $ver$
	 */
	public function test(): void {
		$this->expectException( InvalidArgumentException::class );
		Sort::from_array( [ 'invalid' ] );
	}

	/**
	 * Test case for the `0` key.
	 *
	 * @since $ver$
	 */
	public function testZero(): void {
		$sort = Sort::asc( '0' );
		self::assertSame( [ 'field' => '0', 'direction' => Sort::ASC ], $sort->to_array() );
	}

	/**
	 * Test case for an empty key.
	 *
	 * @since $ver$
	 */
	public function testEmpty(): void {
		$this->expectException( \InvalidArgumentException::class );
		Sort::desc( '' );
	}
}
