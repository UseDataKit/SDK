<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\WooCommerce;

use DataKit\DataViews\Query\Backend\WordPress\AbstractWpdbBackend;
use DataKit\DataViews\Query\Backend\WordPress\WpdbCompiledQuery;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\Source;

/**
 * WooCommerce query backend supporting 3 entities: orders, products, customers.
 *
 * Supports both HPOS (custom orders table) and legacy (wp_posts) modes.
 *
 * @since $ver$
 */
final class WooCommerceBackend extends AbstractWpdbBackend
{
    /**
     * HPOS order columns on wp_wc_orders.
     */
    private const HPOS_ORDER_COLUMNS = [
        'order_id' => 'id',
        'status' => 'status',
        'type' => 'type',
        'date_created' => 'date_created_gmt',
        'date_modified' => 'date_updated_gmt',
        'total_amount' => 'total_amount',
        'tax_amount' => 'tax_amount',
        'currency' => 'currency',
        'payment_method' => 'payment_method',
        'payment_method_title' => 'payment_method_title',
        'customer_id' => 'customer_id',
        'billing_email' => 'billing_email',
        // Semantic aliases.
        'created_at' => 'date_created_gmt',
        'updated_at' => 'date_updated_gmt',
    ];

    /**
     * Legacy order columns on wp_posts.
     */
    private const LEGACY_ORDER_COLUMNS = [
        'order_id' => 'ID',
        'status' => 'post_status',
        'date_created' => 'post_date_gmt',
        'date_modified' => 'post_modified_gmt',
        // Semantic aliases.
        'created_at' => 'post_date_gmt',
        'updated_at' => 'post_modified_gmt',
    ];

    /**
     * Customer lookup columns.
     */
    private const CUSTOMER_COLUMNS = [
        'customer_id' => 'customer_id',
        'user_id' => 'user_id',
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email' => 'email',
        'date_registered' => 'date_registered',
        'date_last_active' => 'date_last_active',
        'orders_count' => 'orders_count',
        'total_spent' => 'total_spent',
        'avg_order_value' => 'avg_order_value',
        // Semantic aliases.
        'created_at' => 'date_registered',
        'updated_at' => 'date_last_active',
    ];

    /**
     * Product columns.
     */
    private const PRODUCT_COLUMNS = [
        'product_id' => 'ID',
        'product_name' => 'post_title',
        'product_status' => 'post_status',
        'date_created' => 'post_date_gmt',
        'date_modified' => 'post_modified_gmt',
        // Semantic aliases.
        'created_at' => 'post_date_gmt',
        'updated_at' => 'post_modified_gmt',
    ];

    /** @var string[] Accumulated JOINs. */
    private array $joins = [];

    private readonly HposDetector $hposDetector;

    public static function isAvailable(): bool
    {
        return class_exists( 'WooCommerce' );
    }

    public function __construct(
        ?HposDetector $hposDetector = null,
    ) {
        parent::__construct();
        $this->hposDetector = $hposDetector ?? new HposDetector();
    }

    public function sourceType(): string
    {
        return 'woocommerce';
    }

    /**
     * Create a source for WooCommerce data.
     *
     * @param string $entity Entity name (default: 'orders').
     * @param array  $scope  Source-specific scope (e.g. ['statuses' => ['wc-completed']]).
     */
    public static function source( string $entity = 'orders', array $scope = [] ): Source {
        return new Source( 'woocommerce', $entity, $scope );
    }

    public function capabilities(): array
    {
        return [
            Capability::FilterEq, Capability::FilterNeq,
            Capability::FilterGt, Capability::FilterGte,
            Capability::FilterLt, Capability::FilterLte,
            Capability::FilterIn, Capability::FilterNotIn,
            Capability::FilterBetween,
            Capability::FilterContains, Capability::FilterNotContains,
            Capability::FilterStartsWith,
            Capability::FilterIsEmpty, Capability::FilterIsNotEmpty,
            Capability::AggCount, Capability::AggSum, Capability::AggAvg,
            Capability::AggMin, Capability::AggMax, Capability::AggCountDistinct,
            Capability::GroupBy, Capability::Having, Capability::OrConditions,
            Capability::TimeBucket, Capability::OrderBy, Capability::LimitOffset,
        ];
    }

    public function describe(array $scope): BackendSchema
    {
        $entity = $scope['entity'] ?? 'orders';
        $allOps = ComparisonOperator::cases();

        $fields = match ($entity) {
            'orders' => $this->describeOrderFields($allOps),
            'products' => $this->describeProductFields($allOps),
            'customers' => $this->describeCustomerFields($allOps),
            default => [],
        };

        return new BackendSchema(
            'woocommerce',
            'WooCommerce ' . ucfirst($entity),
            "WooCommerce {$entity} data" . ($this->hposDetector->isHposEnabled() ? ' (HPOS mode)' : ' (legacy mode)') . '.',
            $this->capabilities(),
            $fields,
        );
    }

    protected function buildColumnMap(Query $query, BackendSchema $schema): array
    {
        $this->joins = [];
        $entity = $query->source->scope['entity'] ?? $query->source->entity ?: 'orders';

        return match ($entity) {
            'orders' => $this->buildOrderColumnMap($query, $schema),
            'products' => $this->buildProductColumnMap($query, $schema),
            'customers' => $this->buildCustomerColumnMap($query, $schema),
            default => [],
        };
    }

    protected function getFrom(Query $query): string
    {
        $entity = $query->source->scope['entity'] ?? $query->source->entity ?: 'orders';

        return match ($entity) {
            'orders' => $this->hposDetector->getOrdersTable() . ' AS o',
            'products' => $this->getProductsTable() . ' AS o',
            'customers' => $this->hposDetector->getCustomerLookupTable() . ' AS c',
            default => '',
        };
    }

    protected function getJoins(): array
    {
        return $this->joins;
    }

    protected function compileScopeWhere(Query $query): array
    {
        $entity = $query->source->scope['entity'] ?? $query->source->entity ?: 'orders';

        return match ($entity) {
            'orders' => $this->orderScopeWhere($query),
            'products' => $this->productScopeWhere(),
            'customers' => ['clause' => '', 'params' => []],
            default => ['clause' => '', 'params' => []],
        };
    }

    protected function estimateRowCount(Query $query): ?int
    {
        global $wpdb;
        $entity = $query->source->scope['entity'] ?? $query->source->entity ?: 'orders';

        return match ($entity) {
            'orders' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . $this->hposDetector->getOrdersTable()
                . ($this->hposDetector->isHposEnabled()
                    ? " WHERE type = 'shop_order' AND status != 'trash'"
                    : " WHERE post_type = 'shop_order' AND post_status != 'trash'"),
            ),
            'products' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'",
            ),
            'customers' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . $this->hposDetector->getCustomerLookupTable(),
            ),
            default => null,
        };
    }

    protected function getResultSchema(WpdbCompiledQuery $compiled): array
    {
        $schema = [];

        foreach ($compiled->columnMap as $key => $expr) {
            $schema[$key] = match (true) {
                str_contains($key, 'date') || $key === 'created_at' || $key === 'updated_at' => ColumnType::Datetime,
                str_ends_with($key, '_bucket') => ColumnType::Datetime,
                str_contains($key, 'amount') || str_contains($key, 'total') || str_contains($key, 'spent')
                    || str_contains($key, 'count') || str_contains($key, 'avg') => ColumnType::Float,
                str_contains($key, 'id') => ColumnType::Integer,
                default => ColumnType::String,
            };
        }

        return $schema;
    }

    // --- Order-specific ---

    private function buildOrderColumnMap(Query $query, BackendSchema $schema): array
    {
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);
        $isHpos = $this->hposDetector->isHposEnabled();
        $columns = $isHpos ? self::HPOS_ORDER_COLUMNS : self::LEGACY_ORDER_COLUMNS;
        $billingJoined = false;
        $shippingJoined = false;

        foreach ($fieldKeys as $key) {
            if (isset($columns[$key])) {
                $columnMap[$key] = 'o.' . $columns[$key];
            } elseif ($isHpos && str_starts_with($key, 'billing_')) {
                if (!$billingJoined) {
                    $this->joins[] = "LEFT JOIN " . $this->hposDetector->getAddressesTable()
                        . " AS ba ON ba.order_id = o.id AND ba.address_type = 'billing'";
                    $billingJoined = true;
                }
                $col = str_replace('billing_', '', $key);
                $columnMap[$key] = "ba.{$col}";
            } elseif ($isHpos && str_starts_with($key, 'shipping_')) {
                if (!$shippingJoined) {
                    $this->joins[] = "LEFT JOIN " . $this->hposDetector->getAddressesTable()
                        . " AS sa ON sa.order_id = o.id AND sa.address_type = 'shipping'";
                    $shippingJoined = true;
                }
                $col = str_replace('shipping_', '', $key);
                $columnMap[$key] = "sa.{$col}";
            } else {
                // Meta field
                $alias = $this->nextJoinAlias();
                $metaTable = $this->hposDetector->getOrderMetaTable();
                $idCol = $isHpos ? 'order_id' : 'post_id';
                $keyCol = $isHpos ? 'meta_key' : 'meta_key';
                $this->joins[] = "LEFT JOIN {$metaTable} AS {$alias} ON {$alias}.{$idCol} = o."
                    . ($isHpos ? 'id' : 'ID')
                    . " AND {$alias}.{$keyCol} = %s";
                $columnMap[$key] = "{$alias}.meta_value";
            }
        }

        return $columnMap;
    }

    private function orderScopeWhere(Query $query): array
    {
        if ($this->hposDetector->isHposEnabled()) {
            return [
                'clause' => "o.type = 'shop_order' AND o.status != 'trash'",
                'params' => [],
            ];
        }

        return [
            'clause' => "o.post_type = 'shop_order' AND o.post_status != 'trash'",
            'params' => [],
        ];
    }

    // --- Product-specific ---

    private function buildProductColumnMap(Query $query, BackendSchema $schema): array
    {
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            if (isset(self::PRODUCT_COLUMNS[$key])) {
                $columnMap[$key] = 'o.' . self::PRODUCT_COLUMNS[$key];
            } else {
                // Meta via product_meta_lookup or postmeta
                $alias = $this->nextJoinAlias();
                $this->joins[] = "LEFT JOIN {$this->getPostMetaTable()} AS {$alias} ON {$alias}.post_id = o.ID AND {$alias}.meta_key = %s";
                $columnMap[$key] = "{$alias}.meta_value";
            }
        }

        return $columnMap;
    }

    private function productScopeWhere(): array
    {
        return [
            'clause' => "o.post_type = 'product' AND o.post_status = 'publish'",
            'params' => [],
        ];
    }

    // --- Customer-specific ---

    private function buildCustomerColumnMap(Query $query, BackendSchema $schema): array
    {
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            if (isset(self::CUSTOMER_COLUMNS[$key])) {
                $columnMap[$key] = 'c.' . self::CUSTOMER_COLUMNS[$key];
            }
        }

        return $columnMap;
    }

    // --- Helpers ---

    private function getProductsTable(): string
    {
        global $wpdb;

        return $wpdb->posts;
    }

    private function getPostMetaTable(): string
    {
        global $wpdb;

        return $wpdb->postmeta;
    }

    private function collectAllFieldKeys(Query $query, BackendSchema $schema): array
    {
        // Browse mode: select all available fields from the schema.
        if ($query->type === QueryType::Browse) {
            $keys = $schema->fieldNames();

            if ($query->time !== null) {
                $keys[] = $query->time->field;
            }
            foreach ($query->orderBy as $o) {
                if ($schema->hasField($o->field)) {
                    $keys[] = $o->field;
                }
            }
            if ($query->where !== null) {
                $this->collectConditionKeys($query->where, $keys);
            }

            return array_unique($keys);
        }

        $keys = [];

        foreach ($query->dimensions as $dim) {
            $keys[] = $dim->field;
        }
        foreach ($query->metrics as $m) {
            if ($m->field !== null) {
                $keys[] = $m->field;
            }
        }
        if ($query->time !== null) {
            $keys[] = $query->time->field;
        }
        foreach ($query->orderBy as $o) {
            if ($schema->hasField($o->field)) {
                $keys[] = $o->field;
            }
        }

        if ($query->where !== null) {
            $this->collectConditionKeys($query->where, $keys);
        }

        return array_unique($keys);
    }

    private function collectConditionKeys(object $group, array &$keys): void
    {
        if ($group instanceof \DataKit\DataViews\Query\ConditionGroup) {
            foreach ($group->conditions as $c) {
                $this->collectConditionKeys($c, $keys);
            }
        } elseif ($group instanceof \DataKit\DataViews\Query\Condition) {
            $keys[] = $group->field;
        }
    }

    // --- Field descriptions ---

    /** @return FieldSchema[] */
    private function describeOrderFields(array $allOps): array
    {
        return [
            new FieldSchema('order_id', 'Order ID', ColumnType::Integer, $allOps),
            new FieldSchema('status', 'Status', ColumnType::String, $allOps,
                enumValues: [
                    'wc-pending' => 'Pending', 'wc-processing' => 'Processing',
                    'wc-on-hold' => 'On Hold', 'wc-completed' => 'Completed',
                    'wc-cancelled' => 'Cancelled', 'wc-refunded' => 'Refunded',
                    'wc-failed' => 'Failed',
                ],
            ),
            new FieldSchema('date_created', 'Date Created', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
            new FieldSchema('date_modified', 'Date Modified', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('total_amount', 'Total Amount', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('currency', 'Currency', ColumnType::String, $allOps),
            new FieldSchema('payment_method', 'Payment Method', ColumnType::String, $allOps),
            new FieldSchema('customer_id', 'Customer ID', ColumnType::Integer, $allOps),
            new FieldSchema('billing_email', 'Billing Email', ColumnType::String, $allOps),
            new FieldSchema('billing_first_name', 'Billing First Name', ColumnType::String, $allOps),
            new FieldSchema('billing_last_name', 'Billing Last Name', ColumnType::String, $allOps),
            new FieldSchema('billing_city', 'Billing City', ColumnType::String, $allOps),
            new FieldSchema('billing_state', 'Billing State', ColumnType::String, $allOps),
            new FieldSchema('billing_country', 'Billing Country', ColumnType::String, $allOps),
            // Semantic aliases.
            new FieldSchema('created_at', 'Created At', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
                description: 'Alias for date_created.',
            ),
            new FieldSchema('updated_at', 'Updated At', ColumnType::Datetime, $allOps,
                timezone: 'utc',
                description: 'Alias for date_modified.',
            ),
        ];
    }

    /** @return FieldSchema[] */
    private function describeProductFields(array $allOps): array
    {
        return [
            new FieldSchema('product_id', 'Product ID', ColumnType::Integer, $allOps),
            new FieldSchema('product_name', 'Product Name', ColumnType::String, $allOps),
            new FieldSchema('product_status', 'Status', ColumnType::String, $allOps),
            new FieldSchema('date_created', 'Date Created', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('date_modified', 'Date Modified', ColumnType::Datetime, $allOps, timezone: 'utc'),
            // Semantic aliases.
            new FieldSchema('created_at', 'Created At', ColumnType::Datetime, $allOps,
                timezone: 'utc',
                description: 'Alias for date_created.',
            ),
            new FieldSchema('updated_at', 'Updated At', ColumnType::Datetime, $allOps,
                timezone: 'utc',
                description: 'Alias for date_modified.',
            ),
        ];
    }

    /** @return FieldSchema[] */
    private function describeCustomerFields(array $allOps): array
    {
        return [
            new FieldSchema('customer_id', 'Customer ID', ColumnType::Integer, $allOps),
            new FieldSchema('user_id', 'User ID', ColumnType::Integer, $allOps),
            new FieldSchema('first_name', 'First Name', ColumnType::String, $allOps),
            new FieldSchema('last_name', 'Last Name', ColumnType::String, $allOps),
            new FieldSchema('email', 'Email', ColumnType::String, $allOps),
            new FieldSchema('date_registered', 'Date Registered', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('date_last_active', 'Last Active', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('orders_count', 'Orders Count', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('total_spent', 'Total Spent', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('avg_order_value', 'Avg Order Value', ColumnType::Float, $allOps, aggregatable: true),
            // Semantic aliases.
            new FieldSchema('created_at', 'Created At', ColumnType::Datetime, $allOps,
                timezone: 'utc',
                description: 'Alias for date_registered.',
            ),
            new FieldSchema('updated_at', 'Updated At', ColumnType::Datetime, $allOps,
                timezone: 'utc',
                description: 'Alias for date_last_active.',
            ),
        ];
    }
}
