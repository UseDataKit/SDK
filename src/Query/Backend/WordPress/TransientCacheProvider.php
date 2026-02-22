<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress;

use DataKit\DataViews\Query\Engine\CacheProvider;

/**
 * CacheProvider implementation using WordPress transients.
 *
 * @since $ver$
 */
final class TransientCacheProvider implements CacheProvider
{
    private const PREFIX = 'dkq_';
    private const TAG_PREFIX = 'dkq_tag_';

    public function get(string $key): ?array
    {
        $value = get_transient(self::PREFIX . $key);

        if ($value === false) {
            return null;
        }

        return is_array($value) ? $value : null;
    }

    public function set(string $key, array $data, int $ttl): void
    {
        set_transient(self::PREFIX . $key, $data, $ttl);
    }

    public function delete(string $key): void
    {
        delete_transient(self::PREFIX . $key);
    }

    public function deleteByTag(string $tag): void
    {
        global $wpdb;

        $prefix = '_transient_' . self::PREFIX;

        // Delete all transients matching the tag pattern
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $prefix . '%' . $tag . '%',
            ),
        );

        // Also delete timeout entries
        $timeoutPrefix = '_transient_timeout_' . self::PREFIX;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $timeoutPrefix . '%' . $tag . '%',
            ),
        );
    }
}
