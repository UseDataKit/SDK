<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\EDD;

/**
 * Resolves EDD 3.0+ custom table names with the WordPress table prefix.
 *
 * EDD 3.0 always uses custom tables — there is no legacy wp_posts storage
 * mode for orders, order addresses, order items, or customers. Each method
 * returns the fully prefixed table name (e.g. `wp_edd_orders`).
 *
 * Table schemas validated against:
 * - `EDD\Database\Tables\Orders` → `{prefix}edd_orders`
 * - `EDD\Database\Tables\Order_Meta` → `{prefix}edd_ordermeta`
 * - `EDD\Database\Tables\Order_Addresses` → `{prefix}edd_order_addresses`
 * - `EDD\Database\Tables\Order_Items` → `{prefix}edd_order_items`
 * - `EDD\Database\Tables\Customers` → `{prefix}edd_customers`
 * - `EDD\Database\Tables\Customer_Meta` → `{prefix}edd_customermeta`
 *
 * @since $ver$
 */
final class EDDTableDetector
{
    /**
     * Get the orders table name.
     *
     * Schema: id, parent, order_number, status, type, user_id, customer_id,
     * email, ip, gateway, mode, currency, payment_key, tax_rate_id,
     * subtotal, discount, tax, total, rate, date_created, date_modified,
     * date_completed, date_refundable, date_actions_run, uuid.
     *
     * @return string Prefixed table name (e.g. `wp_edd_orders`).
     */
    public function getOrdersTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'edd_orders';
    }

    /**
     * Get the order meta table name.
     *
     * Schema: meta_id, edd_order_id (FK → edd_orders.id), meta_key, meta_value.
     *
     * @return string Prefixed table name (e.g. `wp_edd_ordermeta`).
     */
    public function getOrderMetaTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'edd_ordermeta';
    }

    /**
     * Get the order addresses table name.
     *
     * Schema: id, order_id (FK → edd_orders.id), type ('billing'),
     * name, address, address2, city, region, postal_code, country,
     * date_created, date_modified, uuid.
     *
     * @return string Prefixed table name (e.g. `wp_edd_order_addresses`).
     */
    public function getOrderAddressesTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'edd_order_addresses';
    }

    /**
     * Get the order items table name.
     *
     * Schema: id, parent, order_id (FK → edd_orders.id), product_id,
     * product_name, price_id, cart_index, type, status, quantity,
     * amount, subtotal, discount, tax, total, rate,
     * date_created, date_modified, uuid.
     *
     * @return string Prefixed table name (e.g. `wp_edd_order_items`).
     */
    public function getOrderItemsTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'edd_order_items';
    }

    /**
     * Get the customers table name.
     *
     * Schema: id, user_id, email, name, status, purchase_value (decimal(18,9)),
     * purchase_count, date_created, date_modified, uuid.
     *
     * @return string Prefixed table name (e.g. `wp_edd_customers`).
     */
    public function getCustomersTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'edd_customers';
    }

    /**
     * Get the customer meta table name.
     *
     * Schema: meta_id, edd_customer_id (FK → edd_customers.id), meta_key, meta_value.
     *
     * Note: EDD 3.0 renamed the FK column from `customer_id` to `edd_customer_id`.
     *
     * @return string Prefixed table name (e.g. `wp_edd_customermeta`).
     */
    public function getCustomerMetaTable(): string
    {
        global $wpdb;

        return $wpdb->prefix . 'edd_customermeta';
    }

    /**
     * Reset cached state (for testing).
     *
     * No-op for EDD — there is no runtime detection logic to cache,
     * unlike WooCommerce's HPOS mode detection.
     */
    public static function reset(): void
    {
        // No cached state — EDD 3.0 always uses custom tables.
    }
}
