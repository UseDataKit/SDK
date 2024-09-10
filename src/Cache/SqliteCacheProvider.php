<?php

namespace DataKit\DataViews\Cache;

use DataKit\DataViews\Clock\Clock;
use DateInterval;
use DateTimeImmutable;
use Exception;
use PDO;
use RuntimeException;

/**
 * A cache provider backed by SQLite 3 through PDO.
 *
 * @since $ver$
 */
final class SqliteCacheProvider extends BaseCacheProvider {
	/**
	 * The database connection.
	 *
	 * @since $ver$
	 *
	 * @var PDO
	 */
	private PDO $db;

	/**
	 * The file path to the SQLite DB.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $file_path;

	/**
	 * Creates the SQLite Cache Provider.
	 *
	 * @since $ver$
	 *
	 * @param string $file_path The path to the SQL file.
	 */
	public function __construct( string $file_path, ?Clock $clock = null ) {
		if ( ! class_exists( PDO::class ) ) {
			throw new RuntimeException( 'PDO is not installed.' );
		}

		parent::__construct( $clock );
		$this->file_path = $file_path;
	}

	/**
	 * Creates the tables on the database.
	 *
	 * @since $ver$
	 */
	private function create_tables(): void {
		$this->db->exec(
			'CREATE TABLE IF NOT EXISTS `items` (
				item_key VARCHAR(64) NOT NULL PRIMARY KEY,
    			item_value TEXT,
    			expires_at VARCHAR(100) 
			);'
		);

		$this->db->exec(
			'CREATE TABLE if NOT EXISTS `tags` (
				item_tag VARCHAR( 64 ) NOT null,
    			item_key VARCHAR( 64 ) NOT null,
    			PRIMARY KEY( item_tag, item_key )
			);'
		);
	}

	/**
	 * Lazily creates the connection to the database.
	 *
	 * @since $ver$
	 */
	private function init(): void {
		if ( ! isset( $this->db ) ) {
			$this->db = new PDO( 'sqlite:' . $this->file_path );
			$this->create_tables();
		}
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function set( string $key, $value, ?int $ttl = null, array $tags = [] ): void {
		$this->init();

		try {
			$time = (int) $ttl > 0
				? ( $this->clock->now()->add( new DateInterval( 'PT' . $ttl . 'S' ) ) )
				: null;
		} catch ( Exception $e ) {
			throw new \InvalidArgumentException( $e->getMessage(), $e->getCode(), $e );
		}

		$this->db->prepare(
			'INSERT INTO items (item_key, item_value, expires_at) VALUES (:key, :value, :expires_at) 
             ON CONFLICT (item_key) DO UPDATE SET item_value = :value, expires_at = :expires_at'
		)->execute(
			[
				'key'        => $key,
				'value'      => json_encode( $value, JSON_THROW_ON_ERROR ),
				'expires_at' => $time ? $time->format( 'c' ) : null,
			]
		);

		$this->add_tags( $key, $tags );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	protected function doGet( string $key ): ?CacheItem {
		$this->init();

		$statement = $this->db->prepare( 'SELECT * FROM items WHERE item_key = :key' );
		$statement->execute( [ 'key' => $key ] );
		$result = $statement->fetch( PDO::FETCH_ASSOC );
		if ( ! $result ) {
			return null;
		}

		return new CacheItem(
			$key,
			json_decode( $result['item_value'], true ),
			$result['expires_at'] ? new DateTimeImmutable( $result['expires_at'] ) : null
		);
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function delete( string $key ): bool {
		$this->init();

		return $this->db
			->prepare( 'DELETE FROM items WHERE item_key = :key' )
			->execute( [ 'key' => $key ] );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function delete_by_tags( array $tags ): bool {
		$this->init();

		if ( ! $tags ) {
			return true;
		}

		$tags_string = implode(
			',',
			array_map(
				fn( string $tag ): string => $this->db->quote( $tag ),
				$tags
			)
		);

		// Remove items.
		$this->db->exec(
			"DELETE FROM items WHERE item_key IN (
                SELECT item_key FROM tags WHERE item_tag IN ($tags_string)
            );"
		);

		// Remove tags.
		$this->db->exec( "DELETE FROM tags WHERE item_tag IN ($tags_string)" );

		return true;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function clear(): bool {
		if ( file_exists( $this->file_path ) ) {
			@unlink( $this->file_path ); //@phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged
			unset( $this->db );
		}

		$this->init();

		return true;
	}

	/**
	 * Records a key for all provided tags.
	 *
	 * @since $ver$
	 *
	 * @param string $key  The key to tag.
	 * @param array  $tags The tags.
	 */
	private function add_tags( string $key, array $tags ): void {
		if ( ! $tags ) {
			return;
		}

		$query  = 'INSERT INTO tags (item_key, item_tag) VALUES ';
		$values = [];
		$parts  = [];

		foreach ( $tags as $tag ) {
			$values[] = $key;
			$values[] = $tag;

			$parts[] = '(?,?)';
		}

		$this->db->prepare( $query . implode( ',', $parts ) )
			->execute( $values );
	}
}
