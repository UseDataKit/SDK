<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\WooCommerce;

/**
 * Detects whether WooCommerce HPOS (High-Performance Order Storage) is enabled.
 *
 * @since $ver$
 */
final class HposDetector
{
    private static ?bool $hposEnabled = null;

    /**
     * Whether HPOS is enabled (custom orders table).
     */
    public function isHposEnabled(): bool
    {
        if (self::$hposEnabled !== null) {
            return self::$hposEnabled;
        }

        // Check WC option
        $enabled = get_option('woocommerce_custom_orders_table_enabled', 'no');

        if ($enabled !== 'yes') {
            self::$hposEnabled = false;

            return false;
        }

        // Verify the table exists
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $tableExists = $wpdb->get_var(
            $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->prefix . 'wc_orders'),
        );

        self::$hposEnabled = $tableExists !== null;

        return self::$hposEnabled;
    }

    public function getOrdersTable(): string
    {
        global $wpdb;

        return $this->isHposEnabled()
            ? $wpdb->prefix . 'wc_orders'
            : $wpdb->posts;
    }

    public function getAddressesTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'wc_order_addresses';
    }

    public function getOrderMetaTable(): string
    {
        global $wpdb;

        return $this->isHposEnabled()
            ? $wpdb->prefix . 'wc_orders_meta'
            : $wpdb->postmeta;
    }

    public function getProductMetaLookupTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'wc_product_meta_lookup';
    }

    public function getCustomerLookupTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'wc_customer_lookup';
    }

    /**
     * Reset cached state (for testing).
     */
    public static function reset(): void
    {
        self::$hposEnabled = null;
    }
}
