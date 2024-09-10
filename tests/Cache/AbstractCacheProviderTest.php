<?php

namespace DataKit\DataViews\Tests\Cache;

use DataKit\DataViews\Cache\CacheProvider;
use DataKit\DataViews\Clock\Clock;
use DataKit\DataViews\Tests\Clock\FrozenClock;
use PHPUnit\Framework\TestCase;

abstract class AbstractCacheProviderTest extends TestCase {

	abstract protected function create_provider( ?Clock $clock ): CacheProvider;

	/**
	 * Test case entire array cache.
	 *
	 * @since $ver$
	 */
	public function test_cache(): void {
		$cache = $this->create_provider( null );

		self::assertFalse( $cache->has( 'some_key' ) );
		$cache->set( 'some_key', 'A value' );
		$cache->set( 'other_key', 'Other value' );

		self::assertTrue( $cache->has( 'some_key' ) );
		self::assertTrue( $cache->has( 'other_key' ) );

		self::assertSame( 'A value', $cache->get( 'some_key', 'different default' ) );
		self::assertSame( 'different default', $cache->get( 'invalid', 'different default' ) );
		self::assertNull( $cache->get( 'invalid' ) );

		self::assertTrue( $cache->delete( 'some_key' ) );
		self::assertFalse( $cache->has( 'some_key' ) );
		self::assertNull( $cache->get( 'some_key' ) );

		self::assertTrue( $cache->clear() );
		self::assertFalse( $cache->has( 'other_key' ) );
	}

	/**
	 * Test case for a TTL.
	 *
	 * @return void
	 */
	public function test_ttl(): void {
		$clock = new FrozenClock( '2024-06-27 12:34:56' );
		$cache = $this->create_provider( $clock );

		$cache->set( 'some_key', $value = 'This value is stored for 5 seconds.', 5 );
		$cache->set( 'another_key', $value, 5 );

		self::assertTrue( $cache->has( 'some_key' ) );
		self::assertSame( $value, $cache->get( 'another_key' ) );

		$clock->travel_to( '2024-06-27 13:00:00' );
		self::assertFalse( $cache->has( 'some_key' ) );
		self::assertNull( $cache->get( 'another_key' ) );

		$clock->travel_to( '2024-06-27 12:34:54' ); // back in time.
		self::assertFalse( $cache->has( 'some_key' ) );
	}

	public static function dataprovider_for_test_tags(): array {
		return [
			'none'      => [
				[],
				[ 'key_1_1', 'key_1_2', 'key_2_1', 'key_2_2' ],
			],
			'tag_1'     => [
				[ 'tag_1' ],
				[ 'key_2_1', 'key_2_2' ],
			],
			'tag_2'     => [
				[ 'tag_2' ],
				[ 'key_1_1', 'key_1_2' ],
			],
			'both tags' => [
				[ 'tag_1', 'tag_2' ],
				[],
			],
		];
	}

	/**
	 * Test case for cache items with tags.
	 *
	 * @since        $ver$
	 *
	 * @param array $tags_to_delete The tags to delete during the test.
	 * @param array $expected_keys  The keys expected to remain after the deletion.
	 *
	 * @dataProvider dataprovider_for_test_tags The data provider.
	 */
	public function test_tags( array $tags_to_delete, array $expected_keys ): void {
		$cache = $this->create_provider( null );
		$keys  = [ 'key_1_1', 'key_1_2', 'key_2_1', 'key_2_2' ];

		$cache->set( 'key_1_1', 'value 1.1', null, [ 'tag_1' ] );
		$cache->set( 'key_1_2', 'value 1.2', null, [ 'tag_1' ] );

		$cache->set( 'key_2_1', 'value 2.1', null, [ 'tag_2' ] );
		$cache->set( 'key_2_2', 'value 2.2', null, [ 'tag_2' ] );

		foreach ( $keys as $key ) {
			self::assertTrue( $cache->has( $key ) );
		}

		$cache->delete_by_tags( $tags_to_delete );

		foreach ( $keys as $key ) {
			self::assertEquals( in_array( $key, $expected_keys, true ), $cache->has( $key ) );
		}
	}
}
