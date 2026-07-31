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
 * WooCommerce query backend supporting 4 entities: orders, products, customers, subscriptions.
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

    /**
     * HPOS subscription columns on wp_wc_orders (type = 'shop_subscription').
     *
     * Core fields live on the orders table; billing_period and schedule dates
     * are stored in wc_orders_meta and require LEFT JOINs.
     */
    /**
     * Verified against wp_wc_orders DESCRIBE output:
     * id, status, currency, type, tax_amount, total_amount, customer_id,
     * billing_email, date_created_gmt, date_updated_gmt, parent_order_id,
     * payment_method, payment_method_title, transaction_id, ip_address,
     * user_agent, customer_note.
     */
    private const HPOS_SUBSCRIPTION_COLUMNS = [
        'subscription_id'  => 'id',
        'status'           => 'status',
        'customer_id'      => 'customer_id',
        'billing_email'    => 'billing_email',
        'date_created'     => 'date_created_gmt',
        'date_modified'    => 'date_updated_gmt',
        'total_amount'     => 'total_amount',
        'recurring_amount' => 'total_amount',
        'tax_amount'       => 'tax_amount',
        'currency'         => 'currency',
        'payment_method'   => 'payment_method',
        'parent_order_id'  => 'parent_order_id',
        // Semantic aliases.
        'created_at'       => 'date_created_gmt',
        'updated_at'       => 'date_updated_gmt',
    ];

    /**
     * Legacy subscription columns on wp_posts (post_type = 'shop_subscription').
     */
    private const LEGACY_SUBSCRIPTION_COLUMNS = [
        'subscription_id'  => 'ID',
        'status'           => 'post_status',
        'date_created'     => 'post_date_gmt',
        'date_modified'    => 'post_modified_gmt',
        // Semantic aliases.
        'created_at'       => 'post_date_gmt',
        'updated_at'       => 'post_modified_gmt',
    ];

    /**
     * Subscription meta keys stored in wc_orders_meta / wp_postmeta.
     *
     * These fields require LEFT JOINs to the meta table.
     */
    /**
     * Verified against actual wp_wc_orders_meta for shop_subscription rows.
     * Meta keys: _billing_period, _billing_interval, _schedule_start,
     * _schedule_end, _schedule_cancelled, _schedule_next_payment, _trial_period.
     */
    private const SUBSCRIPTION_META_KEYS = [
        'billing_period'    => '_billing_period',
        'billing_interval'  => '_billing_interval',
        'start_date'        => '_schedule_start',
        'end_date'          => '_schedule_end',
        'cancel_date'       => '_schedule_cancelled',
        'next_payment_date' => '_schedule_next_payment',
        'trial_period'      => '_trial_period',
    ];

    /**
     * Product meta keys stored in wp_postmeta.
     *
     * These fields require LEFT JOINs to the postmeta table.
     */
    private const PRODUCT_META_KEYS = [
        'total_sales' => 'total_sales',
        '_price'      => '_price',
        '_regular_price' => '_regular_price',
        '_sale_price' => '_sale_price',
        '_sku'        => '_sku',
        '_stock'      => '_stock',
        '_stock_status' => '_stock_status',
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
            'orders'        => $this->describeOrderFields($allOps),
            'products'      => $this->describeProductFields($allOps),
            'customers'     => $this->describeCustomerFields($allOps),
            'subscriptions' => $this->describeSubscriptionFields($allOps),
            default         => [],
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
            'orders'        => $this->buildOrderColumnMap($query, $schema),
            'products'      => $this->buildProductColumnMap($query, $schema),
            'customers'     => $this->buildCustomerColumnMap($query, $schema),
            'subscriptions' => $this->buildSubscriptionColumnMap($query, $schema),
            default         => [],
        };
    }

    protected function getFrom(Query $query): string
    {
        $entity = $query->source->scope['entity'] ?? $query->source->entity ?: 'orders';

        return match ($entity) {
            'orders'        => $this->hposDetector->getOrdersTable() . ' AS o',
            'products'      => $this->getProductsTable() . ' AS o',
            'customers'     => $this->hposDetector->getCustomerLookupTable() . ' AS c',
            'subscriptions' => $this->hposDetector->getOrdersTable() . ' AS o',
            default         => '',
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
            'orders'        => $this->orderScopeWhere($query),
            'products'      => $this->productScopeWhere(),
            'customers'     => ['clause' => '', 'params' => []],
            'subscriptions' => $this->subscriptionScopeWhere(),
            default         => ['clause' => '', 'params' => []],
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
            'subscriptions' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . $this->hposDetector->getOrdersTable()
                . ($this->hposDetector->isHposEnabled()
                    ? " WHERE type = 'shop_subscription'"
                    : " WHERE post_type = 'shop_subscription'"),
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
            } elseif (isset(self::PRODUCT_META_KEYS[$key])) {
                // Known meta field — LEFT JOIN to postmeta with literal meta_key.
                $alias = $this->nextJoinAlias();
                $metaKey = self::PRODUCT_META_KEYS[$key];
                $this->joins[] = "LEFT JOIN {$this->getPostMetaTable()} AS {$alias} ON {$alias}.post_id = o.ID AND {$alias}.meta_key = '{$metaKey}'";
                $columnMap[$key] = "{$alias}.meta_value";
            } else {
                // Arbitrary meta fallback — use field key as meta_key directly.
                $alias = $this->nextJoinAlias();
                $metaKey = $this->metaKeyLiteral($key);

                if ($metaKey === null) {
                    continue;
                }

                $this->joins[] = "LEFT JOIN {$this->getPostMetaTable()} AS {$alias} ON {$alias}.post_id = o.ID AND {$alias}.meta_key = '{$metaKey}'";
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

    // --- Subscription-specific ---

    /**
     * Build the column map for subscription queries.
     *
     * WooCommerce Subscriptions stores subscriptions as `shop_subscription`
     * type in the orders table. Core fields come from the orders table
     * directly; billing_period and schedule dates are stored in meta.
     */
    private function buildSubscriptionColumnMap(Query $query, BackendSchema $schema): array
    {
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);
        $isHpos = $this->hposDetector->isHposEnabled();
        $columns = $isHpos ? self::HPOS_SUBSCRIPTION_COLUMNS : self::LEGACY_SUBSCRIPTION_COLUMNS;

        foreach ($fieldKeys as $key) {
            if (isset($columns[$key])) {
                $columnMap[$key] = 'o.' . $columns[$key];
            } elseif (isset(self::SUBSCRIPTION_META_KEYS[$key])) {
                // Meta field — LEFT JOIN to meta table.
                $alias = $this->nextJoinAlias();
                $metaTable = $this->hposDetector->getOrderMetaTable();
                $idCol = $isHpos ? 'order_id' : 'post_id';
                $pkCol = $isHpos ? 'id' : 'ID';
                $metaKey = self::SUBSCRIPTION_META_KEYS[$key];
                $this->joins[] = "LEFT JOIN {$metaTable} AS {$alias} ON {$alias}.{$idCol} = o.{$pkCol} AND {$alias}.meta_key = '{$metaKey}'";
                $columnMap[$key] = "{$alias}.meta_value";
            } else {
                // Arbitrary meta fallback.
                $alias = $this->nextJoinAlias();
                $metaTable = $this->hposDetector->getOrderMetaTable();
                $idCol = $isHpos ? 'order_id' : 'post_id';
                $pkCol = $isHpos ? 'id' : 'ID';
                $metaKey = $this->metaKeyLiteral($key);

                if ($metaKey === null) {
                    continue;
                }

                $this->joins[] = "LEFT JOIN {$metaTable} AS {$alias} ON {$alias}.{$idCol} = o.{$pkCol} AND {$alias}.meta_key = '{$metaKey}'";
                $columnMap[$key] = "{$alias}.meta_value";
            }
        }

        return $columnMap;
    }

    private function subscriptionScopeWhere(): array
    {
        if ($this->hposDetector->isHposEnabled()) {
            return [
                'clause' => "o.type = 'shop_subscription'",
                'params' => [],
            ];
        }

        return [
            'clause' => "o.post_type = 'shop_subscription'",
            'params' => [],
        ];
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
            new FieldSchema('total_sales', 'Total Sales', ColumnType::Integer, $allOps,
                aggregatable: true,
                description: 'Lifetime unit sales count from _total_sales postmeta.',
            ),
            new FieldSchema('_price', 'Price', ColumnType::Float, $allOps,
                aggregatable: true,
                description: 'Regular product price from _price postmeta.',
            ),
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

    /**
     * Describe all available subscription fields for the backend schema.
     *
     * WooCommerce Subscriptions stores subscriptions as `shop_subscription`
     * type in the orders table. Schedule dates and billing period are
     * stored as order meta.
     *
     * @param ComparisonOperator[] $allOps All available comparison operators.
     *
     * @return FieldSchema[]
     */
    /**
     * Verified against:
     * - wp_wc_orders DESCRIBE: id, status, customer_id, billing_email,
     *   date_created_gmt, date_updated_gmt, total_amount, tax_amount,
     *   currency, payment_method, parent_order_id.
     * - wp_wc_orders_meta for shop_subscription: _billing_period,
     *   _billing_interval, _schedule_start, _schedule_end,
     *   _schedule_cancelled, _schedule_next_payment, _trial_period.
     */
    private function describeSubscriptionFields(array $allOps): array
    {
        return [
            new FieldSchema('subscription_id', 'Subscription ID', ColumnType::Integer, $allOps),
            new FieldSchema('status', 'Status', ColumnType::String, $allOps,
                enumValues: [
                    'wc-active'         => 'Active',
                    'wc-on-hold'        => 'On Hold',
                    'wc-cancelled'      => 'Cancelled',
                    'wc-expired'        => 'Expired',
                    'wc-pending'        => 'Pending',
                    'wc-pending-cancel' => 'Pending Cancellation',
                ],
            ),
            new FieldSchema('customer_id', 'Customer ID', ColumnType::Integer, $allOps),
            new FieldSchema('billing_email', 'Billing Email', ColumnType::String, $allOps),
            new FieldSchema('billing_period', 'Billing Period', ColumnType::String, $allOps,
                enumValues: [
                    'day'   => 'Day',
                    'week'  => 'Week',
                    'month' => 'Month',
                    'year'  => 'Year',
                ],
            ),
            new FieldSchema('billing_interval', 'Billing Interval', ColumnType::Integer, $allOps),
            new FieldSchema('start_date', 'Start Date', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
            new FieldSchema('end_date', 'End Date', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('cancel_date', 'Cancel Date', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('next_payment_date', 'Next Payment Date', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('trial_period', 'Trial Period', ColumnType::String, $allOps),
            new FieldSchema('recurring_amount', 'Recurring Amount', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('total_amount', 'Total Amount', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('tax_amount', 'Tax Amount', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('date_created', 'Date Created', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
            new FieldSchema('date_modified', 'Date Modified', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('currency', 'Currency', ColumnType::String, $allOps),
            new FieldSchema('payment_method', 'Payment Method', ColumnType::String, $allOps),
            new FieldSchema('parent_order_id', 'Parent Order ID', ColumnType::Integer, $allOps),
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
}
