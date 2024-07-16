<?php

namespace DataKit\DataViews\Tests\Data;

use DataKit\DataViews\Cache\ArrayCacheProvider;
use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\Data\CachedDataSource;
use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Sort;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see CachedDataSource}
 *
 * @since $ver$
 */
final class CachedDataSourceTest extends TestCase {
	/**
	 * The traceable data source.
	 *
	 * @since $ver$
	 * @var TraceableDataSource
	 */
	private TraceableDataSource $trace;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function setUp() : void {
		parent::setUp();

		$this->trace = new TraceableDataSource(
			new ArrayDataSource(
				'test-source',
				[
					'one' => [ 'name' => 'Person one', 'email' => 'person-one@company.test' ],
					'two' => [ 'name' => 'Person two', 'email' => 'person-two@company.test' ],
				],
			),
		);
	}

	/**
	 * Test case for basic caching.
	 *
	 * @since $ver$
	 */
	public function test_caching() : void {
		$cache = new ArrayCacheProvider();
		$ds    = new CachedDataSource( $this->trace, $cache );

		self::assertSame( 'test-source', $ds->id() );

		self::assertSame( [ 'one', 'two' ], $ds->get_data_ids() );
		self::assertSame( [ 'one', 'two' ], $ds->get_data_ids() );

		// Should only be called once, as the cache would take over after the first call (priming).
		self::assertCount( 1, $this->trace->get_calls() );
		self::assertSame( [ 'one', 'two' ], $ds->get_data_ids( 10 ) );

		// Should have a second call, as the parameters changed.
		self::assertCount( 2, $this->trace->get_calls() );

		$expected_result = [
			'name'  => 'Person one',
			'email' => 'person-one@company.test',
			'id'    => 'one',
		];

		self::assertSame( $expected_result, $ds->get_data_by_id( 'one' ) );
		self::assertSame( $expected_result, $ds->get_data_by_id( 'one' ) );

		// Three calls, because the second `get_data_by_id` is cached.
		self::assertCount( 3, $this->trace->get_calls() );

		// Clear the internal cache.
		$cache->clear();

		self::assertSame( $expected_result, $ds->get_data_by_id( 'one' ) );
		self::assertSame( $expected_result, $ds->get_data_by_id( 'one' ) );

		// Four calls because the cache was cleared.
		self::assertCount( 4, $this->trace->get_calls() );

		$ds->get_fields();
		$ds->get_fields();

		// Get fields is always piped through.
		self::assertCount( 6, $this->trace->get_calls() );

		$ds->clear_cache();
		$ds->get_data_ids();
		// Cache cleared.
		self::assertCount( 7, $this->trace->get_calls() );
	}

	/**
	 * Testcase for caching with filters.
	 *
	 * @since $ver$
	 */
	public function test_caching_with_filters() : void {
		$cache = new ArrayCacheProvider();
		$ds    = new CachedDataSource( $this->trace, $cache );

		$ds->get_data_ids();
		$ds->get_data_ids();

		self::assertCount( 1, $this->trace->get_calls() );
		$ds_search = $ds->search_by( 'one' );
		$ds_filter = $ds_search->filter_by( Filters::of( Filter::is( 'name', 'Person one' ) ) );
		$ds_sort   = $ds_filter->sort_by( Sort::asc( 'name' ) );

		self::assertCount( 4, $this->trace->get_calls() );
		self::assertNotSame( $ds_search, $ds );

		self::assertSame( [ 'one' ], $ds_search->get_data_ids() );
		self::assertSame( [ 'one' ], $ds_search->get_data_ids() );
		self::assertSame( [ 'one' ], $ds_filter->get_data_ids() );
		self::assertSame( [ 'one' ], $ds_filter->get_data_ids() );
		self::assertSame( [ 'one' ], $ds_sort->get_data_ids() );
		self::assertSame( [ 'one' ], $ds_sort->get_data_ids() );

		self::assertCount( 7, $this->trace->get_calls() );

		$expected_result = [
			'name'  => 'Person one',
			'email' => 'person-one@company.test',
			'id'    => 'one',
		];

		self::assertSame( $expected_result, $ds->get_data_by_id( 'one' ) );
		self::assertSame( $expected_result, $ds_search->get_data_by_id( 'one' ) );
		self::assertSame( $expected_result, $ds_filter->get_data_by_id( 'one' ) );
		self::assertSame( $expected_result, $ds_sort->get_data_by_id( 'one' ) );

		// The data is not influenced by the filters.
		self::assertCount( 8, $this->trace->get_calls() );
	}

	/**
	 * Test case for {@see CachedDataSource::count()}.
	 *
	 * @since $ver$
	 */
	public function testCount() : void {
		$cache = new ArrayCacheProvider();
		$ds    = new CachedDataSource( $this->trace, $cache );

		self::assertCount( 2, $ds );
		self::assertSame( 2, $ds->count() ); // second call

		self::assertCount( 1, $this->trace->get_calls() );
	}

	/**
	 * Test case for {@see CachedDataSource::can_delete()}.
	 *
	 * @since $ver$
	 */
	public function testCanDelete() : void {
		$cache = new ArrayCacheProvider();
		$ds    = new CachedDataSource( $this->trace, $cache );

		self::assertTrue( $ds->can_delete() );
		self::assertTrue( $ds->can_delete() );

		// Always pass along.
		self::assertCount( 2, $this->trace->get_calls() );
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function testDeleteById() : void {
		$cache = new ArrayCacheProvider();
		$ds    = new CachedDataSource( $this->trace, $cache );

		// Initialize cache.
		self::assertSame( [ 'one', 'two' ], $ds->get_data_ids() );
		self::assertSame( [ 'one', 'two' ], $ds->get_data_ids() );

		self::assertCount( 1, $this->trace->get_calls() );

		$ds->delete_data_by_id( 'one' );
		$ds->delete_data_by_id( 'two' );

		// Always pass along.
		self::assertCount( 3, $this->trace->get_calls() );

		// Cache should be cleared after deletion.
		self::assertSame( [], $ds->get_data_ids() );
	}
}
