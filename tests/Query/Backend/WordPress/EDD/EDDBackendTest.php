<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress\EDD;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\Backend\WordPress\EDD\EDDBackend;
use DataKit\DataViews\Query\Backend\WordPress\EDD\EDDTableDetector;
use DataKit\DataViews\Query\Backend\WordPress\WpdbCompiledQuery;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\Source;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the EDDBackend query backend.
 *
 * Covers all three entities (orders, downloads, customers), column mapping,
 * scope filtering, JOINs (billing address, ordermeta, postmeta, taxonomy,
 * customermeta), schema descriptions, and result type inference.
 */
final class EDDBackendTest extends TestCase
{
    private EDDBackend $backend;

    protected function setUp(): void
    {
        // Stub $wpdb for table name resolution and SQL compilation.
        global $wpdb;
        $wpdb = new class {
            public string $prefix = 'wp_';
            public string $posts = 'wp_posts';
            public string $postmeta = 'wp_postmeta';
            public string $term_relationships = 'wp_term_relationships';
            public string $term_taxonomy = 'wp_term_taxonomy';
            public string $terms = 'wp_terms';

            public function prepare(string $query, ...$args): string
            {
                $i = 0;

                return preg_replace_callback('/%s/', static function () use ($args, &$i) {
                    return "'" . ($args[$i++] ?? '') . "'";
                }, $query);
            }

            public function esc_like(string $text): string
            {
                return addcslashes($text, '_%\\');
            }
        };

        $this->backend = new EDDBackend(new EDDTableDetector());
    }

    // =========================================================================
    // Source type & capabilities
    // =========================================================================

    public function test_source_type_returns_edd(): void
    {
        self::assertSame('edd', $this->backend->sourceType());
    }

    public function test_source_factory_creates_correct_source(): void
    {
        $source = EDDBackend::source('customers', ['statuses' => ['active']]);

        self::assertSame('edd', $source->type);
        self::assertSame('customers', $source->entity);
        self::assertSame(['statuses' => ['active']], $source->scope);
    }

    public function test_capabilities_include_all_expected(): void
    {
        $caps = $this->backend->capabilities();

        self::assertNotEmpty($caps);
        self::assertContains(Capability::FilterEq, $caps);
        self::assertContains(Capability::AggSum, $caps);
        self::assertContains(Capability::TimeBucket, $caps);
        self::assertContains(Capability::GroupBy, $caps);
        self::assertContains(Capability::OrderBy, $caps);
        self::assertContains(Capability::LimitOffset, $caps);
    }

    // =========================================================================
    // Schema: Orders
    // =========================================================================

    /**
     * @dataProvider orderFieldsProvider
     */
    public function test_order_schema_includes_field(string $fieldKey, ColumnType $expectedType): void
    {
        $schema = $this->backend->describe(['entity' => 'orders']);

        self::assertTrue(
            $schema->hasField($fieldKey),
            sprintf('Expected order field "%s" not found. Available: %s', $fieldKey, implode(', ', $schema->fieldNames())),
        );

        $field = $schema->getField($fieldKey);
        self::assertSame($expectedType, $field->type, "Field '{$fieldKey}' has wrong type.");
    }

    public static function orderFieldsProvider(): iterable
    {
        // Direct edd_orders columns.
        yield 'id' => ['id', ColumnType::Integer];
        yield 'order_number' => ['order_number', ColumnType::String];
        yield 'status' => ['status', ColumnType::String];
        yield 'type' => ['type', ColumnType::String];
        yield 'user_id' => ['user_id', ColumnType::Integer];
        yield 'customer_id' => ['customer_id', ColumnType::Integer];
        yield 'email' => ['email', ColumnType::String];
        yield 'gateway' => ['gateway', ColumnType::String];
        yield 'currency' => ['currency', ColumnType::String];
        yield 'subtotal' => ['subtotal', ColumnType::Float];
        yield 'discount' => ['discount', ColumnType::Float];
        yield 'tax' => ['tax', ColumnType::Float];
        yield 'total' => ['total', ColumnType::Float];
        yield 'date_created' => ['date_created', ColumnType::Datetime];
        yield 'date_modified' => ['date_modified', ColumnType::Datetime];
        yield 'date_completed' => ['date_completed', ColumnType::Datetime];
        yield 'ip' => ['ip', ColumnType::String];
        yield 'mode' => ['mode', ColumnType::String];
        // Billing address fields (from edd_order_addresses).
        yield 'billing_name' => ['billing_name', ColumnType::String];
        yield 'billing_address' => ['billing_address', ColumnType::String];
        yield 'billing_city' => ['billing_city', ColumnType::String];
        yield 'billing_region' => ['billing_region', ColumnType::String];
        yield 'billing_postal_code' => ['billing_postal_code', ColumnType::String];
        yield 'billing_country' => ['billing_country', ColumnType::String];
        // Semantic aliases.
        yield 'created_at' => ['created_at', ColumnType::Datetime];
        yield 'updated_at' => ['updated_at', ColumnType::Datetime];
    }

    public function test_order_status_has_enum_values(): void
    {
        $schema = $this->backend->describe(['entity' => 'orders']);
        $field = $schema->getField('status');

        self::assertNotNull($field->enumValues);
        self::assertArrayHasKey('complete', $field->enumValues);
        self::assertArrayHasKey('pending', $field->enumValues);
        self::assertArrayHasKey('refunded', $field->enumValues);
        self::assertArrayHasKey('failed', $field->enumValues);
        self::assertArrayHasKey('abandoned', $field->enumValues);
        self::assertArrayHasKey('partially_refunded', $field->enumValues);
    }

    public function test_order_type_has_enum_values(): void
    {
        $schema = $this->backend->describe(['entity' => 'orders']);
        $field = $schema->getField('type');

        self::assertNotNull($field->enumValues);
        self::assertArrayHasKey('sale', $field->enumValues);
        self::assertArrayHasKey('refund', $field->enumValues);
    }

    public function test_order_mode_has_enum_values(): void
    {
        $schema = $this->backend->describe(['entity' => 'orders']);
        $field = $schema->getField('mode');

        self::assertNotNull($field->enumValues);
        self::assertArrayHasKey('live', $field->enumValues);
        self::assertArrayHasKey('test', $field->enumValues);
    }

    public function test_order_monetary_fields_are_aggregatable(): void
    {
        $schema = $this->backend->describe(['entity' => 'orders']);

        foreach (['subtotal', 'discount', 'tax', 'total'] as $field) {
            self::assertTrue(
                $schema->getField($field)->aggregatable,
                "Order field '{$field}' should be aggregatable.",
            );
        }
    }

    public function test_order_date_created_is_aggregatable(): void
    {
        $schema = $this->backend->describe(['entity' => 'orders']);
        self::assertTrue($schema->getField('date_created')->aggregatable);
        self::assertTrue($schema->getField('created_at')->aggregatable);
    }

    // =========================================================================
    // Schema: Downloads
    // =========================================================================

    /**
     * @dataProvider downloadFieldsProvider
     */
    public function test_download_schema_includes_field(string $fieldKey, ColumnType $expectedType): void
    {
        $schema = $this->backend->describe(['entity' => 'downloads']);

        self::assertTrue(
            $schema->hasField($fieldKey),
            sprintf('Expected download field "%s" not found. Available: %s', $fieldKey, implode(', ', $schema->fieldNames())),
        );

        $field = $schema->getField($fieldKey);
        self::assertSame($expectedType, $field->type, "Field '{$fieldKey}' has wrong type.");
    }

    public static function downloadFieldsProvider(): iterable
    {
        yield 'product_id' => ['product_id', ColumnType::Integer];
        yield 'name' => ['name', ColumnType::String];
        yield 'status' => ['status', ColumnType::String];
        yield 'date_created' => ['date_created', ColumnType::Datetime];
        yield 'price' => ['price', ColumnType::Float];
        yield 'earnings' => ['earnings', ColumnType::Float];
        yield 'sales' => ['sales', ColumnType::Integer];
        yield 'category' => ['category', ColumnType::String];
        yield 'tag' => ['tag', ColumnType::String];
        yield 'created_at' => ['created_at', ColumnType::Datetime];
        yield 'updated_at' => ['updated_at', ColumnType::Datetime];
    }

    public function test_download_monetary_fields_are_aggregatable(): void
    {
        $schema = $this->backend->describe(['entity' => 'downloads']);

        self::assertTrue($schema->getField('price')->aggregatable);
        self::assertTrue($schema->getField('earnings')->aggregatable);
        self::assertTrue($schema->getField('sales')->aggregatable);
    }

    // =========================================================================
    // Schema: Customers
    // =========================================================================

    /**
     * @dataProvider customerFieldsProvider
     */
    public function test_customer_schema_includes_field(string $fieldKey, ColumnType $expectedType): void
    {
        $schema = $this->backend->describe(['entity' => 'customers']);

        self::assertTrue(
            $schema->hasField($fieldKey),
            sprintf('Expected customer field "%s" not found. Available: %s', $fieldKey, implode(', ', $schema->fieldNames())),
        );

        $field = $schema->getField($fieldKey);
        self::assertSame($expectedType, $field->type, "Field '{$fieldKey}' has wrong type.");
    }

    public static function customerFieldsProvider(): iterable
    {
        yield 'customer_id' => ['customer_id', ColumnType::Integer];
        yield 'user_id' => ['user_id', ColumnType::Integer];
        yield 'email' => ['email', ColumnType::String];
        yield 'name' => ['name', ColumnType::String];
        yield 'status' => ['status', ColumnType::String];
        yield 'purchase_value' => ['purchase_value', ColumnType::Float];
        yield 'purchase_count' => ['purchase_count', ColumnType::Integer];
        yield 'date_created' => ['date_created', ColumnType::Datetime];
        yield 'date_modified' => ['date_modified', ColumnType::Datetime];
        yield 'created_at' => ['created_at', ColumnType::Datetime];
        yield 'updated_at' => ['updated_at', ColumnType::Datetime];
    }

    public function test_customer_aggregatable_fields(): void
    {
        $schema = $this->backend->describe(['entity' => 'customers']);
        self::assertTrue($schema->getField('purchase_value')->aggregatable);
        self::assertTrue($schema->getField('purchase_count')->aggregatable);
        self::assertTrue($schema->getField('date_created')->aggregatable);
    }

    // =========================================================================
    // Schema: Default & unknown entities
    // =========================================================================

    public function test_default_entity_is_orders(): void
    {
        $schema = $this->backend->describe([]);
        $schemaOrders = $this->backend->describe(['entity' => 'orders']);

        self::assertSame($schema->fieldNames(), $schemaOrders->fieldNames());
    }

    public function test_unknown_entity_returns_empty_fields(): void
    {
        $schema = $this->backend->describe(['entity' => 'nonexistent']);
        self::assertEmpty($schema->fields);
    }

    // =========================================================================
    // Compile: Order queries
    // =========================================================================

    public function test_order_compile_from_clause(): void
    {
        $compiled = $this->compileAggregate('orders');

        self::assertInstanceOf(WpdbCompiledQuery::class, $compiled);
        self::assertStringContainsString('wp_edd_orders AS o', $compiled->from);
    }

    public function test_order_scope_excludes_trash_and_refunds(): void
    {
        $compiled = $this->compileAggregate('orders');

        $whereStr = implode(' AND ', $compiled->where);
        self::assertStringContainsString("o.type = 'sale'", $whereStr);
        self::assertStringContainsString("o.status != 'trash'", $whereStr);
    }

    public function test_order_scope_with_status_filter(): void
    {
        $compiled = $this->compileAggregate('orders', ['statuses' => ['complete', 'processing']]);

        $whereStr = implode(' AND ', $compiled->where);
        self::assertStringContainsString('o.status IN (', $whereStr);
        self::assertContains('complete', $compiled->params);
        self::assertContains('processing', $compiled->params);
    }

    public function test_order_direct_columns_mapped(): void
    {
        $compiled = $this->compileAggregate('orders', [], ['status']);

        self::assertArrayHasKey('status', $compiled->columnMap);
        self::assertSame('o.status', $compiled->columnMap['status']);
    }

    public function test_order_billing_address_join(): void
    {
        $compiled = $this->compileAggregate('orders', [], ['billing_city', 'billing_country']);

        // Should have a billing address JOIN.
        $joinsStr = implode(' ', $compiled->joins);
        self::assertStringContainsString('wp_edd_order_addresses', $joinsStr);
        self::assertStringContainsString("ba.type = 'billing'", $joinsStr);

        // Only one address JOIN even with multiple billing fields.
        $joinCount = substr_count($joinsStr, 'edd_order_addresses');
        self::assertSame(1, $joinCount, 'Should have exactly one address JOIN.');

        // Column map resolves to ba.* columns.
        self::assertSame('ba.city', $compiled->columnMap['billing_city']);
        self::assertSame('ba.country', $compiled->columnMap['billing_country']);
    }

    public function test_order_meta_fallback_join(): void
    {
        $compiled = $this->compileAggregate('orders', [], ['custom_meta_key']);

        $joinsStr = implode(' ', $compiled->joins);
        self::assertStringContainsString('wp_edd_ordermeta', $joinsStr);
        self::assertStringContainsString('edd_order_id = o.id', $joinsStr);
        self::assertStringContainsString('meta_key = %s', $joinsStr);
    }

    public function test_order_semantic_aliases_map_correctly(): void
    {
        $compiled = $this->compileAggregate('orders', [], ['created_at', 'updated_at']);

        self::assertSame('o.date_created', $compiled->columnMap['created_at']);
        self::assertSame('o.date_modified', $compiled->columnMap['updated_at']);
    }

    // =========================================================================
    // Compile: Download queries
    // =========================================================================

    public function test_download_compile_from_clause(): void
    {
        $compiled = $this->compileAggregate('downloads');

        self::assertStringContainsString('wp_posts AS o', $compiled->from);
    }

    public function test_download_scope_filters_post_type(): void
    {
        $compiled = $this->compileAggregate('downloads');

        $whereStr = implode(' AND ', $compiled->where);
        self::assertStringContainsString("o.post_type = 'download'", $whereStr);
        self::assertStringContainsString("o.post_status != 'trash'", $whereStr);
    }

    public function test_download_direct_columns_mapped(): void
    {
        $compiled = $this->compileAggregate('downloads', [], ['name']);

        self::assertSame('o.post_title', $compiled->columnMap['name']);
    }

    public function test_download_known_meta_key_price(): void
    {
        $compiled = $this->compileAggregate('downloads', [], ['price']);

        $joinsStr = implode(' ', $compiled->joins);
        self::assertStringContainsString('wp_postmeta', $joinsStr);
        self::assertStringContainsString("meta_key = 'edd_price'", $joinsStr);
    }

    public function test_download_known_meta_key_earnings(): void
    {
        $compiled = $this->compileAggregate('downloads', [], ['earnings']);

        $joinsStr = implode(' ', $compiled->joins);
        self::assertStringContainsString("meta_key = '_edd_download_earnings'", $joinsStr);
    }

    public function test_download_known_meta_key_sales(): void
    {
        $compiled = $this->compileAggregate('downloads', [], ['sales']);

        $joinsStr = implode(' ', $compiled->joins);
        self::assertStringContainsString("meta_key = '_edd_download_sales'", $joinsStr);
    }

    public function test_download_category_taxonomy_join(): void
    {
        $compiled = $this->compileAggregate('downloads', [], ['category']);

        $joinsStr = implode(' ', $compiled->joins);
        self::assertStringContainsString('wp_term_relationships', $joinsStr);
        self::assertStringContainsString('wp_term_taxonomy', $joinsStr);
        self::assertStringContainsString('download_category', $joinsStr);
        self::assertStringContainsString('wp_terms', $joinsStr);
    }

    public function test_download_tag_taxonomy_join(): void
    {
        $compiled = $this->compileAggregate('downloads', [], ['tag']);

        $joinsStr = implode(' ', $compiled->joins);
        self::assertStringContainsString('download_tag', $joinsStr);
    }

    public function test_download_arbitrary_meta_fallback(): void
    {
        $compiled = $this->compileAggregate('downloads', [], ['some_custom_meta']);

        $joinsStr = implode(' ', $compiled->joins);
        self::assertStringContainsString('wp_postmeta', $joinsStr);
        self::assertStringContainsString('meta_key = %s', $joinsStr);
    }

    public function test_download_semantic_aliases(): void
    {
        $compiled = $this->compileAggregate('downloads', [], ['created_at', 'updated_at']);

        self::assertSame('o.post_date_gmt', $compiled->columnMap['created_at']);
        self::assertSame('o.post_modified_gmt', $compiled->columnMap['updated_at']);
    }

    // =========================================================================
    // Compile: Customer queries
    // =========================================================================

    public function test_customer_compile_from_clause(): void
    {
        $compiled = $this->compileAggregate('customers');

        self::assertStringContainsString('wp_edd_customers AS c', $compiled->from);
    }

    public function test_customer_no_scope_filter(): void
    {
        $compiled = $this->compileAggregate('customers');

        $whereStr = implode(' AND ', $compiled->where);
        self::assertStringNotContainsString('type =', $whereStr);
        self::assertStringNotContainsString('post_type', $whereStr);
    }

    public function test_customer_direct_columns_mapped(): void
    {
        $compiled = $this->compileAggregate('customers', [], ['email', 'purchase_value']);

        self::assertSame('c.email', $compiled->columnMap['email']);
        self::assertSame('c.purchase_value', $compiled->columnMap['purchase_value']);
    }

    public function test_customer_id_maps_to_c_dot_id(): void
    {
        $compiled = $this->compileAggregate('customers', [], ['customer_id']);

        self::assertSame('c.id', $compiled->columnMap['customer_id']);
    }

    public function test_customer_meta_fallback_join(): void
    {
        $compiled = $this->compileAggregate('customers', [], ['custom_field']);

        $joinsStr = implode(' ', $compiled->joins);
        self::assertStringContainsString('wp_edd_customermeta', $joinsStr);
        self::assertStringContainsString('edd_customer_id = c.id', $joinsStr);
        self::assertStringContainsString('meta_key = %s', $joinsStr);
    }

    public function test_customer_no_joins_for_direct_columns(): void
    {
        $compiled = $this->compileAggregate('customers', [], ['email', 'name', 'status']);

        self::assertEmpty($compiled->joins, 'Direct customer columns should not require JOINs.');
    }

    public function test_customer_semantic_aliases(): void
    {
        $compiled = $this->compileAggregate('customers', [], ['created_at', 'updated_at']);

        self::assertSame('c.date_created', $compiled->columnMap['created_at']);
        self::assertSame('c.date_modified', $compiled->columnMap['updated_at']);
    }

    // =========================================================================
    // Result schema type inference
    // =========================================================================

    public function test_result_schema_date_fields(): void
    {
        $schema = $this->invokeGetResultSchema([
            'date_created' => 'o.date_created',
            'created_at' => 'o.date_created',
            'updated_at' => 'o.date_modified',
            'date_created_bucket' => 'DATE_FORMAT(...)',
        ]);

        self::assertSame(ColumnType::Datetime, $schema['date_created']);
        self::assertSame(ColumnType::Datetime, $schema['created_at']);
        self::assertSame(ColumnType::Datetime, $schema['updated_at']);
        self::assertSame(ColumnType::Datetime, $schema['date_created_bucket']);
    }

    public function test_result_schema_monetary_fields(): void
    {
        $schema = $this->invokeGetResultSchema([
            'total' => 'o.total',
            'subtotal' => 'o.subtotal',
            'discount' => 'o.discount',
            'tax' => 'o.tax',
            'price' => 'm1.meta_value',
            'earnings' => 'm2.meta_value',
            'purchase_value' => 'c.purchase_value',
        ]);

        foreach (['total', 'subtotal', 'discount', 'tax', 'price', 'earnings', 'purchase_value'] as $field) {
            self::assertSame(ColumnType::Float, $schema[$field], "'{$field}' should be Float.");
        }
    }

    public function test_result_schema_integer_fields(): void
    {
        $schema = $this->invokeGetResultSchema([
            'id' => 'o.id',
            'customer_id' => 'o.customer_id',
            'purchase_count' => 'c.purchase_count',
            'sales' => 'm1.meta_value',
        ]);

        self::assertSame(ColumnType::Integer, $schema['id']);
        self::assertSame(ColumnType::Integer, $schema['customer_id']);
        self::assertSame(ColumnType::Integer, $schema['purchase_count']);
        self::assertSame(ColumnType::Integer, $schema['sales']);
    }

    public function test_result_schema_string_fields(): void
    {
        $schema = $this->invokeGetResultSchema([
            'status' => 'o.status',
            'gateway' => 'o.gateway',
            'billing_city' => 'ba.city',
        ]);

        self::assertSame(ColumnType::String, $schema['status']);
        self::assertSame(ColumnType::String, $schema['gateway']);
        self::assertSame(ColumnType::String, $schema['billing_city']);
    }

    public function test_email_field_not_mistyped_as_integer(): void
    {
        // 'email' contains no 'id' substring issue but let's verify.
        $schema = $this->invokeGetResultSchema(['email' => 'o.email']);
        self::assertSame(ColumnType::String, $schema['email'], 'email should be String, not Integer.');
    }

    // =========================================================================
    // Column constants validation (against EDD DB schema)
    // =========================================================================

    public function test_order_columns_match_edd_schema(): void
    {
        $reflection = new \ReflectionClass(EDDBackend::class);
        $orderCols = $reflection->getConstant('ORDER_COLUMNS');

        // All edd_orders table columns we map.
        $expectedDbCols = [
            'id', 'order_number', 'status', 'type', 'user_id', 'customer_id',
            'email', 'gateway', 'currency', 'subtotal', 'discount', 'tax',
            'total', 'date_created', 'date_modified', 'date_completed', 'ip', 'mode',
        ];

        foreach ($expectedDbCols as $col) {
            self::assertArrayHasKey($col, $orderCols, "ORDER_COLUMNS missing key '{$col}'.");
        }

        // Verify semantic aliases.
        self::assertSame('date_created', $orderCols['created_at']);
        self::assertSame('date_modified', $orderCols['updated_at']);
    }

    public function test_billing_columns_match_edd_address_schema(): void
    {
        $reflection = new \ReflectionClass(EDDBackend::class);
        $billingCols = $reflection->getConstant('BILLING_COLUMNS');

        // Exact edd_order_addresses column mappings.
        self::assertSame('name', $billingCols['billing_name']);
        self::assertSame('address', $billingCols['billing_address']);
        self::assertSame('city', $billingCols['billing_city']);
        self::assertSame('region', $billingCols['billing_region']);
        self::assertSame('postal_code', $billingCols['billing_postal_code']);
        self::assertSame('country', $billingCols['billing_country']);
    }

    public function test_customer_columns_match_edd_schema(): void
    {
        $reflection = new \ReflectionClass(EDDBackend::class);
        $customerCols = $reflection->getConstant('CUSTOMER_COLUMNS');

        $expectedDbCols = [
            'customer_id', 'user_id', 'email', 'name', 'status',
            'purchase_value', 'purchase_count', 'date_created', 'date_modified',
        ];

        foreach ($expectedDbCols as $col) {
            self::assertArrayHasKey($col, $customerCols, "CUSTOMER_COLUMNS missing key '{$col}'.");
        }

        // customer_id maps to the PK 'id'.
        self::assertSame('id', $customerCols['customer_id']);
    }

    public function test_download_meta_keys_match_edd_conventions(): void
    {
        $reflection = new \ReflectionClass(EDDBackend::class);
        $metaKeys = $reflection->getConstant('DOWNLOAD_META_KEYS');

        self::assertSame('edd_price', $metaKeys['price']);
        self::assertSame('_edd_download_earnings', $metaKeys['earnings']);
        self::assertSame('_edd_download_sales', $metaKeys['sales']);
    }

    // =========================================================================
    // Table detector
    // =========================================================================

    public function test_table_detector_returns_prefixed_names(): void
    {
        $detector = new EDDTableDetector();

        self::assertSame('wp_edd_orders', $detector->getOrdersTable());
        self::assertSame('wp_edd_ordermeta', $detector->getOrderMetaTable());
        self::assertSame('wp_edd_order_addresses', $detector->getOrderAddressesTable());
        self::assertSame('wp_edd_order_items', $detector->getOrderItemsTable());
        self::assertSame('wp_edd_customers', $detector->getCustomersTable());
        self::assertSame('wp_edd_customermeta', $detector->getCustomerMetaTable());
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Compile an aggregate query with COUNT(*) and optional dimensions.
     *
     * @param string   $entity     Entity name.
     * @param array    $scope      Source scope overrides.
     * @param string[] $dimensions Field keys to use as dimensions.
     *
     * @return WpdbCompiledQuery
     */
    private function compileAggregate(
        string $entity,
        array $scope = [],
        array $dimensions = [],
    ): WpdbCompiledQuery {
        $dims = array_map(
            static fn(string $key) => new SelectField($key),
            $dimensions,
        );

        $query = new Query(
            source: new Source('edd', $entity, $scope),
            type: QueryType::Aggregate,
            dimensions: $dims,
            metrics: [new AggregateField(AggregateFunction::Count, null, 'count_all')],
        );

        $schema = $this->backend->describe(['entity' => $entity] + $scope);
        $compiled = $this->backend->compile($query, $schema);

        self::assertInstanceOf(WpdbCompiledQuery::class, $compiled);

        return $compiled;
    }

    /**
     * Invoke the protected getResultSchema() method via reflection.
     *
     * @param array<string, string> $columnMap Field key → SQL expression map.
     *
     * @return array<string, ColumnType>
     */
    private function invokeGetResultSchema(array $columnMap): array
    {
        // Build a minimal WpdbCompiledQuery with just the columnMap.
        // We need at least one select item to produce valid SQL.
        $selectItems = [];
        foreach ($columnMap as $key => $expr) {
            $selectItems[] = "{$expr} AS `{$key}`";
        }

        $compiled = new WpdbCompiledQuery(
            select: $selectItems,
            from: 'wp_edd_orders AS o',
            columnMap: $columnMap,
        );

        $method = new \ReflectionMethod(EDDBackend::class, 'getResultSchema');

        return $method->invoke($this->backend, $compiled);
    }
}
