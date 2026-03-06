<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query;

use DataKit\DataViews\Query\Backend\WordPress\GravityForms\GravityFormsBackend;
use DataKit\DataViews\Query\Backend\WordPress\WooCommerce\WooCommerceBackend;
use DataKit\DataViews\Query\Backend\WordPress\WordPressUsers\WordPressUsersBackend;
use DataKit\DataViews\Query\Source;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Source value object and backend named constructors.
 */
final class SourceTest extends TestCase
{
    public function test_gravity_forms_produces_correct_array(): void
    {
        $source = GravityFormsBackend::source([1, 2, 3]);

        self::assertSame([
            'type'   => 'gravity_forms',
            'entity' => 'entries',
            'scope'  => [
                'form_ids' => [1, 2, 3],
                'status'   => ['active'],
            ],
        ], $source->toArray());
    }

    public function test_gravity_forms_custom_status(): void
    {
        $source = GravityFormsBackend::source([5], ['active', 'trash']);

        self::assertSame(['active', 'trash'], $source->scope['status']);
    }

    public function test_gravity_forms_entry_wraps_id_in_array(): void
    {
        $source = GravityFormsBackend::sourceForForm(5);

        self::assertSame([
            'type'   => 'gravity_forms',
            'entity' => 'entries',
            'scope'  => [
                'form_ids' => [5],
                'status'   => ['active'],
            ],
        ], $source->toArray());
    }

    public function test_gravity_forms_entry_zero_produces_empty_form_ids(): void
    {
        $source = GravityFormsBackend::sourceForForm(0);

        self::assertSame([], $source->scope['form_ids']);
    }

    public function test_gravity_forms_entry_negative_produces_empty_form_ids(): void
    {
        $source = GravityFormsBackend::sourceForForm(-1);

        self::assertSame([], $source->scope['form_ids']);
    }

    public function test_woo_commerce_defaults(): void
    {
        $source = WooCommerceBackend::source();

        self::assertSame([
            'type'   => 'woocommerce',
            'entity' => 'orders',
        ], $source->toArray());
    }

    public function test_woo_commerce_with_statuses(): void
    {
        $source = WooCommerceBackend::source('orders', ['statuses' => ['wc-completed', 'wc-processing']]);

        self::assertSame([
            'type'   => 'woocommerce',
            'entity' => 'orders',
            'scope'  => [
                'statuses' => ['wc-completed', 'wc-processing'],
            ],
        ], $source->toArray());
    }

    public function test_wordpress_users_defaults(): void
    {
        $source = WordPressUsersBackend::source();

        self::assertSame([
            'type'   => 'wordpress_users',
            'entity' => 'users',
        ], $source->toArray());
    }

    public function test_wordpress_users_with_scope(): void
    {
        $source = WordPressUsersBackend::source(['role' => 'administrator']);

        self::assertSame([
            'type'   => 'wordpress_users',
            'entity' => 'users',
            'scope'  => ['role' => 'administrator'],
        ], $source->toArray());
    }

    public function test_from_array_roundtrip_gravity_forms(): void
    {
        $original = GravityFormsBackend::source([1, 2]);
        $restored = Source::fromArray($original->toArray());

        self::assertSame($original->toArray(), $restored->toArray());
    }

    public function test_from_array_roundtrip_gravity_forms_entry(): void
    {
        $original = GravityFormsBackend::sourceForForm(7);
        $restored = Source::fromArray($original->toArray());

        self::assertSame($original->toArray(), $restored->toArray());
    }

    public function test_from_array_roundtrip_woo_commerce(): void
    {
        $original = WooCommerceBackend::source('orders', ['statuses' => ['wc-completed']]);
        $restored = Source::fromArray($original->toArray());

        self::assertSame($original->toArray(), $restored->toArray());
    }

    public function test_from_array_roundtrip_wordpress_users(): void
    {
        $original = WordPressUsersBackend::source();
        $restored = Source::fromArray($original->toArray());

        self::assertSame($original->toArray(), $restored->toArray());
    }
}
