<?php

namespace DataKit\DataViews\Tests\Data;

use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\Data\DataSource;
use DataKit\DataViews\Data\DataSourceDecorator;
use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Search;
use DataKit\DataViews\DataView\Sort;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see CustomDataSource}
 *
 * @since $ver$
 */
final class DataSourceDecoratorTest extends TestCase {

	private function data_source(): DataSource {
		return new class extends DataSourceDecorator {
			/**
			 * Property to memoize the inner data source.
			 *
			 * @var ArrayDataSource
			 */
			private ArrayDataSource $inner;

			public function id(): string {
				return 'custom';
			}

			public function name(): string {
				return 'My Custom Data Source';
			}

			protected function decorated_datasource(): DataSource {
				// We already instantiated the
				if ( isset( $this->inner ) ) {
					return $this->inner;
				}

				// Retrieve the results
				$results = [ 'one' => [ 'name' => 'Doeke' ], 'two' => [ 'name' => 'Zack' ] ];

				// Instantiate and memoize the inner data source for future calls.
				return $this->inner = new ArrayDataSource( $this->id(), $results );
			}
		};
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_decorator(): void {
		$ds = $this->data_source();
		self::assertSame( [ 'one', 'two' ], $ds->get_data_ids() );
		self::assertSame( [ 'name' => 'Doeke', 'id' => 'one' ], $ds->get_data_by_id( 'one' ) );

		$ds_doeke = $ds->search_by( Search::from_string( 'doeke' ) );
		self::assertSame( [ 'one' ], $ds_doeke->get_data_ids() );

		$ds_filter = $ds->filter_by( Filters::of(
			Filter::is( 'name', 'Zack' ),
		) );

		self::assertCount( 1, $ds_filter );

		$ds_sort = $ds->sort_by( Sort::desc( 'name' ) );
		self::assertSame( [ 'two', 'one' ], $ds_sort->get_data_ids() );
	}
}
