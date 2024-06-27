<?php

namespace DataKit\DataViews\Tests\Data;

use DataKit\DataViews\Cache\ArrayCacheProvider;
use DataKit\DataViews\Data\ArrayDataSource;
use DataKit\DataViews\Data\CachedDataSource;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see CachedDataSource}
 * @since $ver$
 */
final class CachedDataSourceTest extends TestCase {
	/**
	 * The traceable data source.
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
				'Test source',
				[
					'one' => [ 'name' => 'Person one', 'email' => 'person-one@company.test' ],
					'two' => [ 'name' => 'Person two', 'email' => 'person-two@company.test' ],
				]
			)
		);
	}

	/**
	 * Test case for basic caching.
	 * @since $ver$
	 */
	public function test_caching() : void {
		$cache = new ArrayCacheProvider();
		$ds    = new CachedDataSource( $this->trace, $cache );

		self::assertSame( 'test-source', $ds->id() );
		self::assertSame( 'Test source', $ds->name() );

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
			'id'    => 'one'
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
	 * @since $ver$
	 */
	public function test_caching_with_filters() : void {
		$cache = new ArrayCacheProvider();
		$ds    = new CachedDataSource( $this->trace, $cache );

		$ds->get_data_ids();
		$ds->get_data_ids();

		self::assertCount( 1, $this->trace->get_calls() );
		$ds_search = $ds->search_by( 'one' );

		self::assertNotSame( $ds_search, $ds );

		self::assertSame( [ 'one' ], $ds_search->get_data_ids() );
		self::assertSame( [ 'one' ], $ds_search->get_data_ids() );

		self::assertCount( 2, $this->trace->get_calls() );

		$expected_result = [
			'name'  => 'Person one',
			'email' => 'person-one@company.test',
			'id'    => 'one'
		];

		self::assertSame( $expected_result, $ds->get_data_by_id( 'one' ) );
		self::assertSame( $expected_result, $ds_search->get_data_by_id( 'one' ) );

		// The data is not influenced by the filters.
		self::assertCount( 3, $this->trace->get_calls() );
	}
}
