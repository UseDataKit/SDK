<?php

namespace DataKit\DataViews\Tests\Cache;

use DataKit\DataViews\Cache\ArrayCacheProvider;
use DataKit\DataViews\Cache\CacheProvider;
use DataKit\DataViews\Clock\Clock;

/**
 * Unit tests for {@see ArrayCacheProvider}.
 *
 * @since $ver$
 */
final class ArrayCacheProviderTest extends AbstractCacheProviderTest {
	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	protected function create_provider( ?Clock $clock ): CacheProvider {
		return new ArrayCacheProvider( $clock );
	}
}
