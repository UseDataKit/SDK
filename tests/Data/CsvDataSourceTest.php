<?php

namespace DataKit\DataView\Tests\Data;

use DataKit\DataView\Data\CsvDataSource;
use DataKit\DataView\DataView\Filter;
use DataKit\DataView\DataView\Filters;
use DataKit\DataView\DataView\Sort;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see CsvDataSource}
 * @since $ver$
 */
final class CsvDataSourceTest extends TestCase {
	/**
	 * The data source to be tested.
	 * @since $ver$
	 * @var CsvDataSource
	 */
	private CsvDataSource $data_source;

	/**
	 * Set up the data source for every test.
	 * @since $ver$
	 */
	protected function setUp() : void {
		$this->data_source = new CsvDataSource( __DIR__ . '/../assets/oscar-example-data.csv' );
	}

	/**
	 * Test case for {@see CsvDataSource::get_data_by_id()}.
	 * @since $ver$
	 */
	public function test_get_data_by_id() : void {
		self::assertSame(
			[ '82', '2009', '48', 'Sean Penn', 'Milk' ],
			$this->data_source->get_data_by_id( '82' )
		);
	}

	/**
	 * Test case for {@see CsvDataSource::sort_by()}.
	 * @since $ver$
	 */
	public function test_sort_by() : void {
		$sort_by_age = $this->data_source->sort_by( Sort::desc( '2' ) );

		self::assertSame( [ '55', '3', '43', '60', '71' ], $sort_by_age->get_data_ids( 5 ) );
	}

	/**
	 * Test case for {@see CsvDataSource::id()}.
	 * @since $ver$
	 */
	public function test_id() : void {
		self::assertSame( 'csv', $this->data_source->id() );
	}

	/**
	 * Test case for {@see CsvDataSource::search_by()}.
	 * @since $ver$
	 */
	public function test_search_by() : void {
		$search_by_sean_penn = $this->data_source->search_by( 'Sean Penn' );
		self::assertSame( [ '77', '82' ], $search_by_sean_penn->get_data_ids() );
		self::assertCount( 2, $search_by_sean_penn );

		$search_by_robert = $this->data_source->search_by( 'Robert' );
		self::assertSame( [ '13', '42', '54', '57', '72' ], $search_by_robert->get_data_ids() );
		self::assertCount( 5, $search_by_robert );
	}

	/**
	 * Test case for {@see CsvDataSource::name()}.
	 * @since $ver$
	 */
	public function test_name() : void {
		self::assertSame( 'csv-oscar-example-data.csv', $this->data_source->name() );
	}

	/**
	 * Test case for {@see CsvDataSource::count()}.
	 * @since $ver$
	 */
	public function test_count() : void {
		self::assertCount( 89, $this->data_source );
	}

	/**
	 * Test case for {@see CsvDataSource::get_data_ids()}.
	 * @since $ver$
	 */
	public function test_get_data_ids() : void {
		self::assertSame( array_map( 'strval', range( 1, 89 ) ), $this->data_source->get_data_ids() );
	}

	/**
	 * Test case for {@see CsvDataSource::filter_by()}.
	 * @since $ver$
	 */
	public function test_filter_by() : void {
		$data_source = $this->data_source->filter_by(
			Filters::of(
				Filter::is( '3', 'Sean Penn' )
			)
		);

		self::assertCount( 2, $data_source );
		self::assertSame( [ '77', '82' ], $data_source->get_data_ids() );
	}
}
