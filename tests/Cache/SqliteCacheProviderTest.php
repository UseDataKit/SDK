<?php

namespace DataKit\DataViews\Tests\Cache;

use DataKit\DataViews\Cache\CacheProvider;
use DataKit\DataViews\Cache\SqliteCacheProvider;
use DataKit\DataViews\Clock\Clock;
use DataKit\DataViews\Tests\Clock\FrozenClock;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see SqliteCacheProvider}.
 *
 * @since $ver$
 */
final class SqliteCacheProviderTest extends AbstractCacheProviderTest {
	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	protected function create_provider( ?Clock $clock ): CacheProvider {
		$path = dirname( __DIR__ ) . '/assets/test-sqlite.db';
		@unlink( $path ); // Clear for tests.

		return new SqliteCacheProvider( $path, $clock );
	}
}
