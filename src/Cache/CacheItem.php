<?php

namespace DataKit\DataViews\Cache;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * Represents a single cache item.
 *
 * @since $ver$
 */
final class CacheItem {
	/**
	 * The key for the cache item.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $key;

	/**
	 * The cached value.
	 *
	 * @since $ver$
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * The unix timestamp when the cache expires.
	 *
	 * @since $ver$
	 *
	 * @var DateTimeInterface|null
	 */
	private ?DateTimeInterface $expires_at;

	/**
	 * The tags for this cache item.
	 *
	 * @since $ver$
	 *
	 * @var string[]
	 */
	private array $tags;

	/**
	 * Creates the Cache Item.
	 *
	 * @param string                 $key        The cache key.
	 * @param mixed                  $value      The cached value.
	 * @param DateTimeInterface|null $expires_at The timestamp the cache item expires.
	 * @param string[]               $tags       The tags for the cache item.
	 */
	public function __construct( string $key, $value, ?DateTimeInterface $expires_at = null, array $tags = [] ) {
		$this->set_key( $key );
		$this->value      = $value;
		$this->expires_at = $expires_at;

		$this->add_tags( ...$tags );
	}

	/**
	 * Sets the key, and ensures it is valid.
	 *
	 * @since $ver$
	 *
	 * @param string $key The cache key.
	 */
	private function set_key( string $key ): void {
		if ( strlen( $key ) > 64 ) {
			throw new \InvalidArgumentException( 'Cache keys may not exceed a length of 64 characters.' );
		}

		if ( preg_replace( '/[^a-z0-9_.]+/i', '', $key ) !== $key ) {
			throw new \InvalidArgumentException( 'Cache keys may only contain a-z, A-Z, 0-9, underscores (_) and periods (.).' );
		}

		$this->key = $key;
	}

	/**
	 * Returns the key for the cache item.
	 *
	 * @since $ver$
	 *
	 * @return string The cache key.
	 */
	public function key(): string {
		return $this->key;
	}

	/**
	 * Returns the value on the CacheItem.
	 *
	 * @since $ver$
	 *
	 * @return mixed The cached value.
	 */
	public function value() {
		return $this->value;
	}

	/**
	 * Returns the tags for the cache item.
	 *
	 * @since $ver$
	 *
	 * @return string[] The tags.
	 */
	public function tags(): array {
		return $this->tags;
	}

	/**
	 * Returns whether the cache item is expired.
	 *
	 * @param DateTimeImmutable $now The date time to test against.
	 *
	 * @return bool Whether the cache item is expired.
	 */
	public function is_expired( DateTimeImmutable $now ): bool {
		if ( null === $this->expires_at ) {
			return false;
		}

		return $now > $this->expires_at;
	}

	/**
	 * Ensures all tags are strings.
	 *
	 * @since $ver$
	 *
	 * @param string ...$tags The tags.
	 */
	private function add_tags( string ...$tags ): void {
		$this->tags = $tags;
	}
}
