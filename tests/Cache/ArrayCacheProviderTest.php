<?php

namespace DataKit\DataViews\Tests\Cache;

use DataKit\DataViews\Cache\ArrayCacheProvider;
use DataKit\DataViews\Tests\Clock\FrozenClock;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ArrayCacheProvider}
 *
 * @since $ver$
 */
final class ArrayCacheProviderTest extends TestCase {
	/**
	 * Test case entire array cache.
	 *
	 * @since $ver$
	 */
	public function test_cache() : void {
		$cache = new ArrayCacheProvider();

		self::assertFalse( $cache->has( 'some-key' ) );
		$cache->set( 'some-key', 'A value' );
		$cache->set( 'other-key', 'Other value' );

		self::assertTrue( $cache->has( 'some-key' ) );
		self::assertTrue( $cache->has( 'other-key' ) );

		self::assertSame( 'A value', $cache->get( 'some-key', 'different default' ) );
		self::assertSame( 'different default', $cache->get( 'invalid', 'different default' ) );
		self::assertNull( $cache->get( 'invalid' ) );

		self::assertTrue( $cache->delete( 'some-key' ) );
		self::assertFalse( $cache->has( 'some-key' ) );
		self::assertNull( $cache->get( 'some-key' ) );

		self::assertTrue( $cache->clear() );
		self::assertFalse( $cache->has( 'other-key' ) );
	}

	/**
	 * Test case for a TTL.
	 *
	 * @return void
	 */
	public function test_ttl() : void {
		$clock = new FrozenClock( '2024-06-27 12:34:56' );
		$cache = new ArrayCacheProvider( $clock );

		$cache->set( 'some-key', $value = 'This value is stored for 5 seconds.', 5 );
		$cache->set( 'another-key', $value, 5 );

		self::assertTrue( $cache->has( 'some-key' ) );
		self::assertSame( $value, $cache->get( 'another-key' ) );

		$clock->travel_to( '2024-06-27 13:00:00' );
		self::assertFalse( $cache->has( 'some-key' ) );
		self::assertNull( $cache->get( 'another-key' ) );

		$clock->travel_to( '2024-06-27 12:34:54' ); // back in time.
		self::assertFalse( $cache->has( 'some-key' ) );
	}
}
