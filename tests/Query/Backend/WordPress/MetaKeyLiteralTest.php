<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress;

use DataKit\DataViews\Query\Backend\WordPress\EDD\EDDBackend;
use DataKit\DataViews\Query\Backend\WordPress\GravityForms\GravityFormsBackend;
use DataKit\DataViews\Query\Backend\WordPress\WooCommerce\WooCommerceBackend;
use DataKit\DataViews\Query\Backend\WordPress\WordPressUsers\WordPressUsersBackend;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * A meta key reaches SQL as a quoted literal, not as a bound parameter.
 *
 * EAV JOINs are assembled as raw strings — `AND {$alias}.meta_key = '{$key}'`
 * — because the join list carries no parameter channel. GravityFormsBackend
 * guards its keys with a character allow-list and returns null for the rest;
 * EDD, WooCommerce and WordPressUsers took the field key straight from the
 * query and interpolated it, so a single quote in the key closed the literal.
 *
 * Same rule, one implementation, applied by every backend that builds one of
 * these joins.
 */
final class MetaKeyLiteralTest extends TestCase
{
    private function guard(object $backend, string $key): ?string
    {
        return (new ReflectionMethod($backend, 'metaKeyLiteral'))->invoke($backend, $key);
    }

    /**
     * @return array<string, array{object}>
     */
    public static function backends(): array
    {
        return [
            'edd' => [new EDDBackend()],
            'woocommerce' => [new WooCommerceBackend()],
            'wordpress_users' => [new WordPressUsersBackend()],
            'gravityforms' => [new GravityFormsBackend()],
        ];
    }

    /**
     * @dataProvider backends
     */
    public function test_a_quote_in_a_meta_key_is_refused(object $backend): void
    {
        self::assertNull($this->guard($backend, "_edd_payment_total' OR 1=1 --"));
    }

    /**
     * @dataProvider backends
     */
    public function test_a_backslash_is_refused(object $backend): void
    {
        self::assertNull($this->guard($backend, '_key\\'));
    }

    /**
     * @dataProvider backends
     */
    public function test_an_empty_key_is_refused(object $backend): void
    {
        self::assertNull($this->guard($backend, ''));
    }

    /**
     * Positive control. Without this the assertions above would hold for a
     * guard that refused everything, which is not the behaviour being fixed.
     *
     * @dataProvider backends
     */
    public function test_ordinary_meta_keys_pass_through(object $backend): void
    {
        self::assertSame('_edd_payment_total', $this->guard($backend, '_edd_payment_total'));
        self::assertSame('_billing.first-name', $this->guard($backend, '_billing.first-name'));
        self::assertSame('1.3', $this->guard($backend, '1.3'));
    }

    /**
     * The wiring: no backend may interpolate a bare key into a meta_key
     * literal. A guard nothing calls is the same as no guard.
     */
    public function test_no_backend_interpolates_an_unguarded_key(): void
    {
        $files = glob(__DIR__ . '/../../../../src/Query/Backend/WordPress/*/*Backend.php') ?: [];

        self::assertNotEmpty($files, 'Found no backends; the glob is broken, not the code.');

        $offenders = [];

        foreach ($files as $file) {
            $source = (string) file_get_contents($file);

            // `meta_key = '{$something}'` where something is not a variable
            // this class already ran through the guard.
            if (preg_match_all('/meta_key\s*=\s*\'\{\$(\w+)\}\'/', $source, $matches)) {
                foreach ($matches[1] as $var) {
                    if (!in_array($var, ['metaKey'], true)) {
                        $offenders[] = basename($file) . ': $' . $var;
                    }
                }
            }
        }

        self::assertSame([], $offenders, 'These interpolate a key that never went through metaKeyLiteral().');
    }
}
