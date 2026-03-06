<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\EDD;

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
 * Easy Digital Downloads 3.0+ query backend.
 *
 * Supports three entities:
 *
 * - **orders** — from `edd_orders` custom table, with optional JOINs to
 *   `edd_order_addresses` (billing fields) and `edd_ordermeta` (custom meta).
 *   EDD 3.0 always uses custom tables; there is no legacy wp_posts storage.
 *
 * - **downloads** — from `wp_posts` (post_type = 'download'), with JOINs to
 *   `wp_postmeta` for price/earnings/sales and taxonomy tables for categories/tags.
 *
 * - **customers** — from `edd_customers` custom table, with optional JOINs to
 *   `edd_customermeta` for custom meta fields.
 *
 * Column maps and FK column names are validated against EDD 3.0 schema definitions:
 * - `edd_ordermeta.edd_order_id` (FK to edd_orders.id)
 * - `edd_customermeta.edd_customer_id` (FK to edd_customers.id)
 * - `edd_order_addresses.type` = 'billing' (address type discriminator)
 *
 * @since $ver$
 */
final class EDDBackend extends AbstractWpdbBackend
{
    /**
     * Column map for the `edd_orders` table.
     *
     * Maps field keys used in queries to actual database column names.
     * Includes semantic aliases (`created_at`, `updated_at`) that point
     * to the canonical date columns for cross-backend compatibility.
     *
     * Validated against: EDD\Database\Tables\Orders::set_schema()
     *
     * @var array<string, string>
     */
    private const ORDER_COLUMNS = [
        'id'             => 'id',
        'order_number'   => 'order_number',
        'status'         => 'status',
        'type'           => 'type',
        'user_id'        => 'user_id',
        'customer_id'    => 'customer_id',
        'email'          => 'email',
        'gateway'        => 'gateway',
        'currency'       => 'currency',
        'subtotal'       => 'subtotal',
        'discount'       => 'discount',
        'tax'            => 'tax',
        'total'          => 'total',
        'date_created'   => 'date_created',
        'date_modified'  => 'date_modified',
        'date_completed' => 'date_completed',
        'ip'             => 'ip',
        'mode'           => 'mode',
        // Semantic aliases for cross-backend compatibility.
        'created_at'     => 'date_created',
        'updated_at'     => 'date_modified',
    ];

    /**
     * Column map for billing address fields on `edd_order_addresses`.
     *
     * These fields require a LEFT JOIN to `edd_order_addresses` with
     * `type = 'billing'`. The JOIN is only added when one of these
     * fields is referenced in the query.
     *
     * Validated against: EDD\Database\Tables\Order_Addresses::set_schema()
     *
     * @var array<string, string>
     */
    private const BILLING_COLUMNS = [
        'billing_name'        => 'name',
        'billing_address'     => 'address',
        'billing_city'        => 'city',
        'billing_region'      => 'region',
        'billing_postal_code' => 'postal_code',
        'billing_country'     => 'country',
    ];

    /**
     * Column map for download (product) fields on `wp_posts`.
     *
     * Downloads are stored as WordPress posts with `post_type = 'download'`.
     * Meta fields (price, earnings, sales) are resolved via `wp_postmeta`.
     *
     * @var array<string, string>
     */
    private const DOWNLOAD_COLUMNS = [
        'product_id'   => 'ID',
        'name'         => 'post_title',
        'status'       => 'post_status',
        'date_created' => 'post_date_gmt',
        // Semantic aliases.
        'created_at'   => 'post_date_gmt',
        'updated_at'   => 'post_modified_gmt',
    ];

    /**
     * Known EDD postmeta keys for download fields.
     *
     * These are hardcoded meta_key values used by EDD to store download
     * pricing and sales data. Using hardcoded keys avoids %s placeholder
     * JOINs and allows direct meta_key matching in the JOIN clause.
     *
     * @var array<string, string>
     */
    private const DOWNLOAD_META_KEYS = [
        'price'    => 'edd_price',
        'earnings' => '_edd_download_earnings',
        'sales'    => '_edd_download_sales',
    ];

    /**
     * Column map for the `edd_customers` table.
     *
     * Validated against: EDD\Database\Tables\Customers::set_schema()
     * Note: `purchase_value` is decimal(18,9) in the DB schema.
     *
     * @var array<string, string>
     */
    private const CUSTOMER_COLUMNS = [
        'customer_id'    => 'id',
        'user_id'        => 'user_id',
        'email'          => 'email',
        'name'           => 'name',
        'status'         => 'status',
        'purchase_value' => 'purchase_value',
        'purchase_count' => 'purchase_count',
        'date_created'   => 'date_created',
        'date_modified'  => 'date_modified',
        // Semantic aliases.
        'created_at'     => 'date_created',
        'updated_at'     => 'date_modified',
    ];

    /**
     * Accumulated JOIN clauses built during column map construction.
     *
     * Reset at the start of each {@see buildColumnMap()} call.
     *
     * @var string[]
     */
    private array $joins = [];

    /**
     * Table name detector for EDD custom tables.
     *
     * @var EDDTableDetector
     */
    private readonly EDDTableDetector $tableDetector;

    /**
     * Check whether the EDD plugin is active.
     *
     * @return bool True if the Easy_Digital_Downloads class exists.
     */
    public static function isAvailable(): bool
    {
        return class_exists('Easy_Digital_Downloads');
    }

    /**
     * @param EDDTableDetector|null $tableDetector Optional detector for testability.
     */
    public function __construct(
        ?EDDTableDetector $tableDetector = null,
    ) {
        parent::__construct();
        $this->tableDetector = $tableDetector ?? new EDDTableDetector();
    }

    /**
     * {@inheritDoc}
     */
    public function sourceType(): string
    {
        return 'edd';
    }

    /**
     * Create a Source instance for EDD data.
     *
     * @param string $entity Entity name: 'orders', 'downloads', or 'customers'.
     * @param array  $scope  Optional scope constraints (e.g. ['statuses' => ['complete']]).
     *
     * @return Source
     */
    public static function source(string $entity = 'orders', array $scope = []): Source
    {
        return new Source('edd', $entity, $scope);
    }

    /**
     * {@inheritDoc}
     *
     * @return Capability[]
     */
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

    /**
     * Describe the schema for a given EDD entity.
     *
     * Returns field definitions including data types, supported operators,
     * and enum values for categorical fields (status, type, mode).
     *
     * @param array $scope Must include 'entity' key ('orders', 'downloads', or 'customers').
     *
     * @return BackendSchema
     */
    public function describe(array $scope): BackendSchema
    {
        $entity = $scope['entity'] ?? 'orders';
        $allOps = ComparisonOperator::cases();

        $fields = match ($entity) {
            'orders'    => $this->describeOrderFields($allOps),
            'downloads' => $this->describeDownloadFields($allOps),
            'customers' => $this->describeCustomerFields($allOps),
            default     => [],
        };

        return new BackendSchema(
            'edd',
            'EDD ' . ucfirst($entity),
            "Easy Digital Downloads {$entity} data.",
            $this->capabilities(),
            $fields,
        );
    }

    /**
     * Build the column map for this query.
     *
     * Dispatches to entity-specific builders which populate {@see $joins}
     * as a side effect when address, meta, or taxonomy JOINs are needed.
     *
     * {@inheritDoc}
     */
    protected function buildColumnMap(Query $query, BackendSchema $schema): array
    {
        $this->joins = [];
        $entity = $query->source->scope['entity'] ?? $query->source->entity ?: 'orders';

        return match ($entity) {
            'orders'    => $this->buildOrderColumnMap($query, $schema),
            'downloads' => $this->buildDownloadColumnMap($query, $schema),
            'customers' => $this->buildCustomerColumnMap($query, $schema),
            default     => [],
        };
    }

    /**
     * Get the FROM clause for the primary table.
     *
     * - orders: `{prefix}edd_orders AS o`
     * - downloads: `{prefix}posts AS o`
     * - customers: `{prefix}edd_customers AS c`
     *
     * {@inheritDoc}
     */
    protected function getFrom(Query $query): string
    {
        $entity = $query->source->scope['entity'] ?? $query->source->entity ?: 'orders';

        return match ($entity) {
            'orders'    => $this->tableDetector->getOrdersTable() . ' AS o',
            'downloads' => $this->getPostsTable() . ' AS o',
            'customers' => $this->tableDetector->getCustomersTable() . ' AS c',
            default     => '',
        };
    }

    /**
     * {@inheritDoc}
     *
     * @return string[]
     */
    protected function getJoins(): array
    {
        return $this->joins;
    }

    /**
     * Compile entity-specific scope WHERE conditions.
     *
     * - orders: `type = 'sale' AND status != 'trash'`, plus optional status filtering.
     * - downloads: `post_type = 'download' AND post_status != 'trash'`.
     * - customers: no scope restriction (all rows).
     *
     * {@inheritDoc}
     */
    protected function compileScopeWhere(Query $query): array
    {
        $entity = $query->source->scope['entity'] ?? $query->source->entity ?: 'orders';

        return match ($entity) {
            'orders'    => $this->orderScopeWhere($query),
            'downloads' => $this->downloadScopeWhere(),
            'customers' => ['clause' => '', 'params' => []],
            default     => ['clause' => '', 'params' => []],
        };
    }

    /**
     * Estimate the total row count for cost calculation.
     *
     * Uses simple COUNT(*) queries with scope filters applied. The result
     * feeds into {@see AbstractWpdbBackend::estimate()} for query cost scoring.
     *
     * {@inheritDoc}
     */
    protected function estimateRowCount(Query $query): ?int
    {
        global $wpdb;
        $entity = $query->source->scope['entity'] ?? $query->source->entity ?: 'orders';

        return match ($entity) {
            'orders' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . $this->tableDetector->getOrdersTable()
                . " WHERE type = 'sale' AND status != 'trash'",
            ),
            'downloads' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'download' AND post_status = 'publish'",
            ),
            'customers' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM " . $this->tableDetector->getCustomersTable(),
            ),
            default => null,
        };
    }

    /**
     * Derive the result schema (column types) from the compiled column map.
     *
     * Uses field key naming conventions to infer types:
     * - Date/time fields and `_bucket` suffixes → Datetime
     * - Monetary fields (total, subtotal, discount, tax, price, earnings, purchase_value) → Float
     * - Count/sales fields → Integer
     * - ID fields (excluding email) → Integer
     * - Everything else → String
     *
     * {@inheritDoc}
     */
    protected function getResultSchema(WpdbCompiledQuery $compiled): array
    {
        $schema = [];

        foreach ($compiled->columnMap as $key => $expr) {
            $schema[$key] = match (true) {
                str_contains($key, 'date') || $key === 'created_at' || $key === 'updated_at' => ColumnType::Datetime,
                str_ends_with($key, '_bucket') => ColumnType::Datetime,
                str_contains($key, 'total') || str_contains($key, 'subtotal')
                    || str_contains($key, 'discount') || str_contains($key, 'tax')
                    || str_contains($key, 'price') || str_contains($key, 'earnings')
                    || str_contains($key, 'purchase_value') => ColumnType::Float,
                str_contains($key, 'count') || str_contains($key, 'sales') => ColumnType::Integer,
                str_contains($key, 'id') && !str_contains($key, 'email') => ColumnType::Integer,
                default => ColumnType::String,
            };
        }

        return $schema;
    }

    // -------------------------------------------------------------------------
    // Order entity
    // -------------------------------------------------------------------------

    /**
     * Build the column map for order queries.
     *
     * Resolves fields in this priority:
     * 1. Direct columns on `edd_orders` (via ORDER_COLUMNS).
     * 2. Billing address fields via LEFT JOIN to `edd_order_addresses`
     *    (joined once, filtered by `type = 'billing'`).
     * 3. Custom meta via LEFT JOIN to `edd_ordermeta`
     *    (FK: `edd_order_id`, one JOIN per meta key).
     *
     * @param Query         $query  The query being compiled.
     * @param BackendSchema $schema The entity schema for field discovery.
     *
     * @return array<string, string> Field key → SQL expression map.
     */
    private function buildOrderColumnMap(Query $query, BackendSchema $schema): array
    {
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);
        $addressJoined = false;

        foreach ($fieldKeys as $key) {
            if (isset(self::ORDER_COLUMNS[$key])) {
                $columnMap[$key] = 'o.' . self::ORDER_COLUMNS[$key];
            } elseif (isset(self::BILLING_COLUMNS[$key])) {
                if (!$addressJoined) {
                    $this->joins[] = "LEFT JOIN " . $this->tableDetector->getOrderAddressesTable()
                        . " AS ba ON ba.order_id = o.id AND ba.type = 'billing'";
                    $addressJoined = true;
                }
                $columnMap[$key] = 'ba.' . self::BILLING_COLUMNS[$key];
            } else {
                // Custom meta field via edd_ordermeta.
                $alias = $this->nextJoinAlias();
                $metaTable = $this->tableDetector->getOrderMetaTable();
                $this->joins[] = "LEFT JOIN {$metaTable} AS {$alias} ON {$alias}.edd_order_id = o.id AND {$alias}.meta_key = %s";
                $columnMap[$key] = "{$alias}.meta_value";
            }
        }

        return $columnMap;
    }

    /**
     * Compile scope WHERE for order queries.
     *
     * Always filters to `type = 'sale'` (excludes refund rows) and
     * `status != 'trash'`. Optionally filters by specific statuses
     * if provided in the query source scope.
     *
     * @param Query $query The query (may contain scope['statuses']).
     *
     * @return array{clause: string, params: array}
     */
    private function orderScopeWhere(Query $query): array
    {
        $clauses = ["o.type = 'sale'", "o.status != 'trash'"];
        $params = [];

        // Support status filtering via scope (e.g. ['statuses' => ['complete', 'processing']]).
        $statuses = $query->source->scope['statuses'] ?? null;
        if (is_array($statuses) && $statuses !== []) {
            $placeholders = implode(', ', array_fill(0, count($statuses), '%s'));
            $clauses[] = "o.status IN ({$placeholders})";
            $params = array_merge($params, $statuses);
        }

        return [
            'clause' => implode(' AND ', $clauses),
            'params' => $params,
        ];
    }

    // -------------------------------------------------------------------------
    // Download (product) entity
    // -------------------------------------------------------------------------

    /**
     * Build the column map for download queries.
     *
     * Resolves fields in this priority:
     * 1. Direct columns on `wp_posts` (via DOWNLOAD_COLUMNS).
     * 2. Known EDD meta keys via LEFT JOIN to `wp_postmeta`
     *    (hardcoded meta_key values from DOWNLOAD_META_KEYS).
     * 3. Taxonomy fields (category, tag) via triple JOIN to
     *    `term_relationships` → `term_taxonomy` → `terms`.
     * 4. Arbitrary postmeta fallback for unknown fields.
     *
     * @param Query         $query  The query being compiled.
     * @param BackendSchema $schema The entity schema for field discovery.
     *
     * @return array<string, string> Field key → SQL expression map.
     */
    private function buildDownloadColumnMap(Query $query, BackendSchema $schema): array
    {
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            if (isset(self::DOWNLOAD_COLUMNS[$key])) {
                $columnMap[$key] = 'o.' . self::DOWNLOAD_COLUMNS[$key];
            } elseif (isset(self::DOWNLOAD_META_KEYS[$key])) {
                // Known EDD meta key — use hardcoded meta_key in JOIN.
                $alias = $this->nextJoinAlias();
                $metaKey = self::DOWNLOAD_META_KEYS[$key];
                $this->joins[] = "LEFT JOIN {$this->getPostMetaTable()} AS {$alias} ON {$alias}.post_id = o.ID AND {$alias}.meta_key = '{$metaKey}'";
                $columnMap[$key] = "{$alias}.meta_value";
            } elseif ($key === 'category' || $key === 'tag') {
                // Taxonomy fields resolved via term relationship chain.
                $taxonomy = $key === 'category' ? 'download_category' : 'download_tag';
                $alias = $this->nextJoinAlias('tr');
                $ttAlias = $this->nextJoinAlias('tt');
                $tAlias = $this->nextJoinAlias('t');
                global $wpdb;
                $this->joins[] = "LEFT JOIN {$wpdb->term_relationships} AS {$alias} ON {$alias}.object_id = o.ID";
                $this->joins[] = "LEFT JOIN {$wpdb->term_taxonomy} AS {$ttAlias} ON {$ttAlias}.term_taxonomy_id = {$alias}.term_taxonomy_id AND {$ttAlias}.taxonomy = '{$taxonomy}'";
                $this->joins[] = "LEFT JOIN {$wpdb->terms} AS {$tAlias} ON {$tAlias}.term_id = {$ttAlias}.term_id";
                $columnMap[$key] = "{$tAlias}.name";
            } else {
                // Arbitrary postmeta fallback.
                $alias = $this->nextJoinAlias();
                $this->joins[] = "LEFT JOIN {$this->getPostMetaTable()} AS {$alias} ON {$alias}.post_id = o.ID AND {$alias}.meta_key = %s";
                $columnMap[$key] = "{$alias}.meta_value";
            }
        }

        return $columnMap;
    }

    /**
     * Compile scope WHERE for download queries.
     *
     * Restricts to `post_type = 'download'` and excludes trashed downloads.
     *
     * @return array{clause: string, params: array}
     */
    private function downloadScopeWhere(): array
    {
        return [
            'clause' => "o.post_type = 'download' AND o.post_status != 'trash'",
            'params' => [],
        ];
    }

    // -------------------------------------------------------------------------
    // Customer entity
    // -------------------------------------------------------------------------

    /**
     * Build the column map for customer queries.
     *
     * Resolves fields in this priority:
     * 1. Direct columns on `edd_customers` (via CUSTOMER_COLUMNS).
     * 2. Custom meta via LEFT JOIN to `edd_customermeta`
     *    (FK: `edd_customer_id`, one JOIN per meta key).
     *
     * @param Query         $query  The query being compiled.
     * @param BackendSchema $schema The entity schema for field discovery.
     *
     * @return array<string, string> Field key → SQL expression map.
     */
    private function buildCustomerColumnMap(Query $query, BackendSchema $schema): array
    {
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            if (isset(self::CUSTOMER_COLUMNS[$key])) {
                $columnMap[$key] = 'c.' . self::CUSTOMER_COLUMNS[$key];
            } else {
                // Customer meta via edd_customermeta.
                $alias = $this->nextJoinAlias();
                $metaTable = $this->tableDetector->getCustomerMetaTable();
                $this->joins[] = "LEFT JOIN {$metaTable} AS {$alias} ON {$alias}.edd_customer_id = c.id AND {$alias}.meta_key = %s";
                $columnMap[$key] = "{$alias}.meta_value";
            }
        }

        return $columnMap;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Get the wp_posts table name.
     *
     * @return string Prefixed table name.
     */
    private function getPostsTable(): string
    {
        global $wpdb;

        return $wpdb->posts;
    }

    /**
     * Get the wp_postmeta table name.
     *
     * @return string Prefixed table name.
     */
    private function getPostMetaTable(): string
    {
        global $wpdb;

        return $wpdb->postmeta;
    }

    /**
     * Collect all field keys referenced by the query.
     *
     * In Browse mode, returns all schema fields plus any fields referenced
     * in time filters, ORDER BY, or WHERE conditions. In Aggregate mode,
     * returns only fields explicitly used in dimensions, metrics, time,
     * ORDER BY, and WHERE.
     *
     * @param Query         $query  The query to inspect.
     * @param BackendSchema $schema The backend schema for field enumeration.
     *
     * @return string[] Unique field keys.
     */
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

    /**
     * Recursively collect field keys from a condition tree.
     *
     * @param object   $group ConditionGroup or Condition instance.
     * @param string[] $keys  Accumulator array (passed by reference).
     */
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

    // -------------------------------------------------------------------------
    // Field schema descriptions
    // -------------------------------------------------------------------------

    /**
     * Describe all available order fields for the backend schema.
     *
     * Includes direct edd_orders columns, billing address fields,
     * and semantic aliases (created_at, updated_at). Status enum values
     * match EDD 3.0 order statuses (without wc- prefix).
     *
     * @param ComparisonOperator[] $allOps All available comparison operators.
     *
     * @return FieldSchema[]
     */
    private function describeOrderFields(array $allOps): array
    {
        return [
            new FieldSchema('id', 'Order ID', ColumnType::Integer, $allOps),
            new FieldSchema('order_number', 'Order Number', ColumnType::String, $allOps),
            new FieldSchema('status', 'Status', ColumnType::String, $allOps,
                enumValues: [
                    'complete'           => 'Complete',
                    'pending'            => 'Pending',
                    'processing'         => 'Processing',
                    'refunded'           => 'Refunded',
                    'partially_refunded' => 'Partially Refunded',
                    'revoked'            => 'Revoked',
                    'failed'             => 'Failed',
                    'abandoned'          => 'Abandoned',
                ],
            ),
            new FieldSchema('type', 'Type', ColumnType::String, $allOps,
                enumValues: ['sale' => 'Sale', 'refund' => 'Refund'],
            ),
            new FieldSchema('user_id', 'User ID', ColumnType::Integer, $allOps),
            new FieldSchema('customer_id', 'Customer ID', ColumnType::Integer, $allOps),
            new FieldSchema('email', 'Email', ColumnType::String, $allOps),
            new FieldSchema('gateway', 'Payment Gateway', ColumnType::String, $allOps),
            new FieldSchema('currency', 'Currency', ColumnType::String, $allOps),
            new FieldSchema('subtotal', 'Subtotal', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('discount', 'Discount', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('tax', 'Tax', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('total', 'Total', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('date_created', 'Date Created', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
            new FieldSchema('date_modified', 'Date Modified', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('date_completed', 'Date Completed', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('ip', 'IP Address', ColumnType::String, $allOps),
            new FieldSchema('mode', 'Mode', ColumnType::String, $allOps,
                enumValues: ['live' => 'Live', 'test' => 'Test'],
            ),
            new FieldSchema('billing_name', 'Billing Name', ColumnType::String, $allOps),
            new FieldSchema('billing_address', 'Billing Address', ColumnType::String, $allOps),
            new FieldSchema('billing_city', 'Billing City', ColumnType::String, $allOps),
            new FieldSchema('billing_region', 'Billing Region', ColumnType::String, $allOps),
            new FieldSchema('billing_postal_code', 'Billing Postal Code', ColumnType::String, $allOps),
            new FieldSchema('billing_country', 'Billing Country', ColumnType::String, $allOps),
            // Semantic aliases for cross-backend time field resolution.
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

    /**
     * Describe all available download (product) fields for the backend schema.
     *
     * Downloads are WordPress posts (post_type = 'download'). Meta fields
     * (price, earnings, sales) and taxonomy fields (category, tag) are
     * resolved via JOINs during query compilation.
     *
     * @param ComparisonOperator[] $allOps All available comparison operators.
     *
     * @return FieldSchema[]
     */
    private function describeDownloadFields(array $allOps): array
    {
        return [
            new FieldSchema('product_id', 'Download ID', ColumnType::Integer, $allOps),
            new FieldSchema('name', 'Download Name', ColumnType::String, $allOps),
            new FieldSchema('status', 'Status', ColumnType::String, $allOps,
                enumValues: [
                    'publish' => 'Published',
                    'draft'   => 'Draft',
                    'pending' => 'Pending',
                    'private' => 'Private',
                ],
            ),
            new FieldSchema('date_created', 'Date Created', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('price', 'Price', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('earnings', 'Total Earnings', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('sales', 'Total Sales', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('category', 'Category', ColumnType::String, $allOps),
            new FieldSchema('tag', 'Tag', ColumnType::String, $allOps),
            // Semantic aliases.
            new FieldSchema('created_at', 'Created At', ColumnType::Datetime, $allOps,
                timezone: 'utc',
                description: 'Alias for date_created (post_date_gmt).',
            ),
            new FieldSchema('updated_at', 'Updated At', ColumnType::Datetime, $allOps,
                timezone: 'utc',
                description: 'Alias for date_modified (post_modified_gmt).',
            ),
        ];
    }

    /**
     * Describe all available customer fields for the backend schema.
     *
     * Maps directly to `edd_customers` table columns. The `purchase_value`
     * column is decimal(18,9) in the database, represented as Float here.
     *
     * @param ComparisonOperator[] $allOps All available comparison operators.
     *
     * @return FieldSchema[]
     */
    private function describeCustomerFields(array $allOps): array
    {
        return [
            new FieldSchema('customer_id', 'Customer ID', ColumnType::Integer, $allOps),
            new FieldSchema('user_id', 'User ID', ColumnType::Integer, $allOps),
            new FieldSchema('email', 'Email', ColumnType::String, $allOps),
            new FieldSchema('name', 'Name', ColumnType::String, $allOps),
            new FieldSchema('status', 'Status', ColumnType::String, $allOps),
            new FieldSchema('purchase_value', 'Total Purchase Value', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('purchase_count', 'Purchase Count', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('date_created', 'Date Created', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
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
}
