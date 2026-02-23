<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress\GravityForms;

use DataKit\DataViews\Query\Backend\WordPress\GravityForms\GravityFormsBackend;
use PHPUnit\Framework\TestCase;

/**
 * Tests that GravityFormsBackend exposes the expected fields in its default schema.
 */
final class GravityFormsBackendFieldsTest extends TestCase
{
    private GravityFormsBackend $backend;

    protected function setUp(): void
    {
        $this->backend = new GravityFormsBackend();
    }

    /**
     * @dataProvider expectedFieldsProvider
     */
    public function test_default_schema_includes_field(string $fieldKey): void
    {
        $schema = $this->backend->describe([]);

        self::assertTrue(
            $schema->hasField($fieldKey),
            sprintf(
                'Expected field "%s" not found in schema. Available: %s',
                $fieldKey,
                implode(', ', $schema->fieldNames()),
            ),
        );
    }

    /**
     * Verify that form_title (a joined virtual field) is in the schema.
     */
    public function test_form_title_is_virtual_field(): void
    {
        $schema = $this->backend->describe([]);
        $field = $schema->getField('form_title');

        self::assertNotNull($field, 'form_title should be in schema');
        self::assertSame('Form title', $field->label);
    }

    /**
     * Verify that the JOINED_COLUMNS mapping correctly adds form_title to the column map.
     * Uses reflection to call buildColumnMap since it's protected.
     */
    public function test_joined_columns_constant_includes_form_title(): void
    {
        $reflection = new \ReflectionClass(GravityFormsBackend::class);
        $constant = $reflection->getConstant('JOINED_COLUMNS');

        self::assertArrayHasKey('form_title', $constant);
        self::assertSame('title', $constant['form_title']['column']);
        self::assertSame('gf_form', $constant['form_title']['alias']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function expectedFieldsProvider(): iterable
    {
        // All system columns from wp_gf_entry
        yield 'entry_id' => ['entry_id'];
        yield 'form_id' => ['form_id'];
        yield 'status' => ['status'];
        yield 'date_created' => ['date_created'];
        yield 'date_updated' => ['date_updated'];
        yield 'ip' => ['ip'];
        yield 'source_url' => ['source_url'];
        yield 'user_agent' => ['user_agent'];
        yield 'currency' => ['currency'];
        yield 'payment_status' => ['payment_status'];
        yield 'payment_date' => ['payment_date'];
        yield 'payment_amount' => ['payment_amount'];
        yield 'payment_method' => ['payment_method'];
        yield 'transaction_id' => ['transaction_id'];
        yield 'is_starred' => ['is_starred'];
        yield 'is_read' => ['is_read'];
        yield 'created_by' => ['created_by'];
        yield 'transaction_type' => ['transaction_type'];
        yield 'post_id' => ['post_id'];
        yield 'is_fulfilled' => ['is_fulfilled'];

        // Virtual joined columns
        yield 'form_title' => ['form_title'];
    }
}
