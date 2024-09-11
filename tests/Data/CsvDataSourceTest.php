<?php

namespace DataKit\DataViews\Tests\Data;

use DataKit\DataViews\Data\CsvDataSource;
use DataKit\DataViews\Data\Exception\DataNotFoundException;
use DataKit\DataViews\Data\Exception\DataSourceNotFoundException;
use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Search;
use DataKit\DataViews\DataView\Sort;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see CsvDataSource}
 *
 * @since $ver$
 */
final class CsvDataSourceTest extends TestCase {
	/**
	 * The data source to be tested.
	 *
	 * @since $ver$
	 * @var CsvDataSource
	 */
	private CsvDataSource $data_source;

	/**
	 * Set up the data source for every test.
	 *
	 * @since $ver$
	 */
	protected function setUp(): void {
		$this->data_source = new CsvDataSource( __DIR__ . '/../assets/oscar-example-data.csv' );
	}

	/**
	 * Test case for missing or unreadable path.
	 *
	 * @since $ver$
	 */
	public function test_invalid_path(): void {
		$this->expectException( DataSourceNotFoundException::class );
		new CsvDataSource( 'invalid-path' );
	}

	/**
	 * Test case for {@see CsvDataSource::get_data_by_id()}.
	 *
	 * @since $ver$
	 */
	public function test_get_data_by_id(): void {
		self::assertSame(
			[ '82', '2009', '48', 'Sean Penn', 'Milk' ],
			$this->data_source->get_data_by_id( '82' ),
		);

		$this->expectException( DataNotFoundException::class );
		$this->data_source->get_data_by_id( 'invalid' );
	}

	/**
	 * Test case for {@see CsvDataSource::sort_by()}.
	 *
	 * @since $ver$
	 */
	public function test_sort_by(): void {
		$sort_by_age = $this->data_source->sort_by( Sort::desc( '2' ) );

		$data = array_map(
			static fn( string $id ): array => $sort_by_age->get_data_by_id( $id ),
			$sort_by_age->get_data_ids( 3 ),
		);
		$ages = array_column( $data, '2' );
		self::assertSame( [ '83', '76', '62' ], $ages );
	}

	/**
	 * Test case for {@see CsvDataSource::id()}.
	 *
	 * @since $ver$
	 */
	public function test_id(): void {
		self::assertSame( 'csv-oscar-example-data.csv', $this->data_source->id() );
	}

	/**
	 * Test case for {@see CsvDataSource::search_by()}.
	 *
	 * @since $ver$
	 */
	public function test_search_by(): void {
		$search_by_sean_penn = $this->data_source->search_by( Search::from_string( 'Sean Penn' ) );
		self::assertSame( [ '77', '82' ], $search_by_sean_penn->get_data_ids() );
		self::assertCount( 2, $search_by_sean_penn );

		$search_by_robert = $this->data_source->search_by( Search::from_string( 'Robert' ) );
		self::assertSame( [ '13', '42', '54', '57', '72' ], $search_by_robert->get_data_ids() );
		self::assertCount( 5, $search_by_robert );
	}

	/**
	 * Test case for {@see CsvDataSource::count()}.
	 *
	 * @since $ver$
	 */
	public function test_count(): void {
		self::assertCount( 97, $this->data_source );
	}

	/**
	 * Test case for {@see CsvDataSource::get_data_ids()}.
	 *
	 * @since $ver$
	 */
	public function test_get_data_ids(): void {
		self::assertSame( array_map( 'strval', range( 1, 97 ) ), $this->data_source->get_data_ids() );
	}

	/**
	 * Test case for {@see CsvDataSource::filter_by()}.
	 *
	 * @since $ver$
	 */
	public function test_filter_by(): void {
		$data_source = $this->data_source->filter_by(
			Filters::of(
				Filter::is( '3', 'Sean Penn' ),
			),
		);

		self::assertCount( 2, $data_source );
		self::assertSame( [ '77', '82' ], $data_source->get_data_ids() );
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function testGetFields(): void {
		self::assertSame(
			[ 'Index', 'Year', 'Age', 'Name', 'Movie' ],
			$this->data_source->get_fields(),
		);
	}
}
