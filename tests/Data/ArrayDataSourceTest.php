<?php

namespace DataKit\DataView\Tests\Data;

use DataKit\DataView\Data\ArrayDataSource;
use DataKit\DataView\DataView\Filter;
use DataKit\DataView\DataView\Filters;
use DataKit\DataView\DataView\Sort;
use PHPUnit\Framework\TestCase;


/**
 * Unit tests for {@see ArrayDataSource}
 * @since $ver$
 */
final class ArrayDataSourceTest extends TestCase {
	/**
	 * Test case for
	 * @since $ver$
	 */
	public function test_data_source() : void {
		$source = new ArrayDataSource(
			'test',
			'Test data set',
			[
				'user::1' => [
					'name'  => 'Zack',
					'email' => 'zack@gravitykit.com',
				],
				'user::2' => [
					'name'  => 'Vlad',
					'email' => 'vlad@gravitykit.com',
				],
				'user::3' => [
					'name'  => 'Doeke',
					'email' => 'doeke@gravitykit.com',
				],
			],
		);

		self::assertSame( 'test', $source->id() );
		self::assertSame( 'Test data set', $source->name() );

		self::assertCount( 3, $source );
		self::assertSame( [ 'user::1', 'user::2', 'user::3' ], $source->get_data_ids() );
		self::assertSame(
			[ 'name' => 'Vlad', 'email' => 'vlad@gravitykit.com' ],
			$source->get_data_by_id( 'user::2' ),
		);

		self::assertSame( [ 'user::1' ], $source->get_data_ids( 1 ) );
		self::assertSame( [ 'user::3' ], $source->get_data_ids( 1, 2 ) );

		$asc  = $source->sort_by( Sort::asc( 'name' ) );
		$desc = $source->sort_by( Sort::desc( 'name' ) );
		self::assertSame( [ 'user::3', 'user::2', 'user::1' ], $asc->get_data_ids() );
		self::assertSame( [ 'user::1', 'user::2', 'user::3' ], $desc->get_data_ids() );

		$not_doeke = $source->filter_by(
			Filters::of(
				Filter::isNot( 'name', 'Doeke' ),
			)
		);

		self::assertSame( [ 'user::1', 'user::2' ], $not_doeke->get_data_ids() );
		self::assertCount(2, $not_doeke);
	}
}
