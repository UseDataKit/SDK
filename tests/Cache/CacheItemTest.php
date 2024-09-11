<?php

namespace DataKit\DataViews\Tests\Cache;

use DataKit\DataViews\Cache\CacheItem;
use DataKit\DataViews\Tests\Clock\FrozenClock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see CacheItem}
 *
 * @since $ver$
 */
final class CacheItemTest extends TestCase {
	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_cache_item(): void {
		$clock      = new FrozenClock( '2024-09-10 10:35:00' );
		$timestamp  = new DateTimeImmutable('2024-09-10 10:35:05');
		$serialized = serialize( new CacheItem( 'key_1', 'value-1', $timestamp, [ 'tag_1', 'tag_2' ] ) );
		$item       = unserialize( $serialized );

		self::assertSame( 'key_1', $item->key() );
		self::assertSame( 'value-1', $item->value() );
		self::assertSame( [ 'tag_1', 'tag_2' ], $item->tags() );

		self::assertFalse( $item->is_expired( $clock->now() ) );
		$clock->travel_to( '2024-09-10 10:36:00' );
		self::assertTrue( $item->is_expired( $clock->now() ) );
	}
}
