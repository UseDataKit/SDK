<?php

namespace DataKit\DataViews\Tests\Data;

use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\Data\Exception\DataNotFoundException;
use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Search;
use DataKit\DataViews\DataView\Sort;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Unit tests for {@see ArrayDataSource}
 *
 * @since $ver$
 */
final class ArrayDataSourceTest extends TestCase {
	/**
	 * The data source under test.
	 *
	 * @since $ver$
	 * @var ArrayDataSource
	 */
	private ArrayDataSource $source;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function setUp(): void {
		$this->source = new ArrayDataSource(
			'test',
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
					'name'      => 'Doeke',
					'email'     => 'doeke@gravitykit.com',
					'extra_key' => 'Extra value',
				],
			],
		);
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_data_source(): void {
		self::assertSame( 'test', $this->source->id() );

		self::assertCount( 3, $this->source );
		self::assertSame( [ 'user::1', 'user::2', 'user::3' ], $this->source->get_data_ids() );
		self::assertSame(
			[ 'name' => 'Vlad', 'email' => 'vlad@gravitykit.com', 'id' => 'user::2' ],
			$this->source->get_data_by_id( 'user::2' ),
		);

		self::assertSame( [ 'user::1' ], $this->source->get_data_ids( 1 ) );
		self::assertSame( [ 'user::3' ], $this->source->get_data_ids( 1, 2 ) );

		$asc  = $this->source->sort_by( Sort::asc( 'name' ) );
		$desc = $this->source->sort_by( Sort::desc( 'name' ) );
		self::assertSame( [ 'user::3', 'user::2', 'user::1' ], $asc->get_data_ids() );
		self::assertSame( [ 'user::1', 'user::2', 'user::3' ], $desc->get_data_ids() );

		$not_doeke = $this->source->filter_by(
			Filters::of(
				Filter::isNot( 'name', 'Doeke' ),
			),
		);

		self::assertSame( [ 'user::1', 'user::2' ], $not_doeke->get_data_ids() );
		self::assertCount( 2, $not_doeke );

		$search_by_vlad = $this->source->search_by( Search::from_string( 'vlad' ) );
		self::assertSame( [ 'user::2' ], $search_by_vlad->get_data_ids() );

		$search_by_zack_or_vlad = $this->source->search_by( Search::from_string( 'vlad zack' ) );
		self::assertSame( [ 'user::1', 'user::2' ], $search_by_zack_or_vlad->get_data_ids() );
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_not_found(): void {
		$this->expectException( DataNotFoundException::class );
		$this->source->get_data_by_id( 'invalid' );
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_delete_by_id(): void {
		$data = $this->source->get_data_by_id( 'user::1' );
		self::assertSame( 'Zack', $data['name'] );
		self::assertTrue( $this->source->can_delete() );

		$this->source->delete_data_by_id( 'user::1', 'user::2' );
		self::assertSame( [ 'user::3' ], $this->source->get_data_ids() );

		try {
			$this->source->delete_data_by_id( 'user::1' );
		} catch ( Throwable $e ) {
			self::assertInstanceOf( DataNotFoundException::class, $e );
			self::assertSame( $this->source, $e->data_source() );
		}
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_get_fields(): void {
		self::assertSame(
			[ 'name' => 'name', 'email' => 'email', 'extra_key' => 'extra_key' ],
			$this->source->get_fields(),
		);
	}
}
