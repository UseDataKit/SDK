<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\WPQuery;

use DataKit\DataViews\Query\Backend\WordPress\AbstractWpdbBackend;
use DataKit\DataViews\Query\Backend\WordPress\SqlFilterCompiler;
use DataKit\DataViews\Query\Backend\WordPress\WpdbCompiledQuery;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;

/**
 * WordPress Posts/Pages/CPT query backend.
 *
 * Queries wp_posts with LEFT JOIN wp_postmeta per meta field.
 *
 * @since $ver$
 */
final class WPQueryBackend extends AbstractWpdbBackend
{
    /**
     * System columns on wp_posts that don't require JOINs.
     */
    private const POST_COLUMNS = [
        'post_id'            => 'ID',
        'post_author'        => 'post_author',
        'post_date'          => 'post_date',
        'post_date_gmt'      => 'post_date_gmt',
        'post_title'         => 'post_title',
        'post_excerpt'       => 'post_excerpt',
        'post_content'       => 'post_content',
        'post_status'        => 'post_status',
        'post_name'          => 'post_name',
        'post_modified'      => 'post_modified',
        'post_modified_gmt'  => 'post_modified_gmt',
        'post_parent'        => 'post_parent',
        'post_type'          => 'post_type',
        'post_mime_type'     => 'post_mime_type',
        'comment_count'      => 'comment_count',
        'menu_order'         => 'menu_order',
        // Semantic aliases used by template specs.
        'created_at'         => 'post_date',
        'updated_at'         => 'post_modified',
    ];

    /**
     * Virtual columns that require JOINs to other tables (not postmeta).
     */
    private const JOINED_COLUMNS = [
        'author_name' => [
            'alias'  => 'u_author',
            'column' => 'display_name',
        ],
    ];

    private const DATETIME_COLUMNS = ['post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'created_at', 'updated_at'];
    private const NUMERIC_COLUMNS = ['post_id', 'post_author', 'post_parent', 'comment_count', 'menu_order'];

    /** @var string[] Accumulated JOIN clauses during column map building. */
    private array $joins = [];

    public static function isAvailable(): bool
    {
        return true;
    }

    public function sourceType(): string
    {
        return 'wp_query';
    }

    public function capabilities(): array
    {
        return [
            // All filter capabilities.
            Capability::FilterEq, Capability::FilterNeq,
            Capability::FilterGt, Capability::FilterGte,
            Capability::FilterLt, Capability::FilterLte,
            Capability::FilterIn, Capability::FilterNotIn,
            Capability::FilterBetween,
            Capability::FilterContains, Capability::FilterNotContains,
            Capability::FilterStartsWith,
            Capability::FilterIsEmpty, Capability::FilterIsNotEmpty,
            // All aggregate capabilities.
            Capability::AggCount, Capability::AggSum, Capability::AggAvg,
            Capability::AggMin, Capability::AggMax, Capability::AggCountDistinct,
            // Structural.
            Capability::GroupBy, Capability::Having, Capability::OrConditions,
            Capability::TimeBucket, Capability::Search, Capability::OrderBy,
            Capability::LimitOffset,
        ];
    }

    public function describe(array $scope): BackendSchema
    {
        $allOps = ComparisonOperator::cases();

        $fields = [
            new FieldSchema('post_id', 'Post ID', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('post_author', 'Author ID', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('post_date', 'Date', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'local',
            ),
            new FieldSchema('post_date_gmt', 'Date (UTC)', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
            new FieldSchema('post_title', 'Title', ColumnType::String, $allOps),
            new FieldSchema('post_excerpt', 'Excerpt', ColumnType::String, $allOps, sortable: false),
            new FieldSchema('post_content', 'Content', ColumnType::String, [],
                sortable: false, filterable: false,
                description: 'Full post content. Not filterable due to size.',
            ),
            new FieldSchema('post_status', 'Status', ColumnType::String, $allOps,
                enumValues: [
                    'publish' => 'Published',
                    'draft'   => 'Draft',
                    'pending' => 'Pending',
                    'private' => 'Private',
                    'trash'   => 'Trash',
                    'future'  => 'Future',
                ],
            ),
            new FieldSchema('post_name', 'Slug', ColumnType::String, $allOps),
            new FieldSchema('post_modified', 'Modified', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'local',
            ),
            new FieldSchema('post_modified_gmt', 'Modified (UTC)', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
            new FieldSchema('post_parent', 'Parent ID', ColumnType::Integer, $allOps),
            new FieldSchema('post_type', 'Post Type', ColumnType::String, $allOps),
            new FieldSchema('post_mime_type', 'MIME Type', ColumnType::String, $allOps),
            new FieldSchema('comment_count', 'Comment Count', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('menu_order', 'Menu Order', ColumnType::Integer, $allOps),
            // Joined virtual columns.
            new FieldSchema('author_name', 'Author Name', ColumnType::String, $allOps,
                description: 'Display name joined from wp_users.',
            ),
            // Semantic aliases.
            new FieldSchema('created_at', 'Created At', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'local',
                description: 'Alias for post_date (site timezone).',
            ),
            new FieldSchema('updated_at', 'Updated At', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'local',
                description: 'Alias for post_modified (site timezone).',
            ),
        ];

        return new BackendSchema(
            'wp_query',
            'WordPress Posts',
            'WordPress posts, pages, and custom post types with metadata.',
            $this->capabilities(),
            $fields,
        );
    }

    protected function buildColumnMap(Query $query, BackendSchema $schema): array
    {
        global $wpdb;

        $this->joins = [];
        $columnMap = [];
        $addedJoinedTables = [];

        $fieldKeys = $this->collectFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            if (isset(self::POST_COLUMNS[$key])) {
                $columnMap[$key] = 'p.' . self::POST_COLUMNS[$key];
            } elseif (isset(self::JOINED_COLUMNS[$key])) {
                $joinDef = self::JOINED_COLUMNS[$key];
                $alias = $joinDef['alias'];
                $columnMap[$key] = "{$alias}.{$joinDef['column']}";

                if (!isset($addedJoinedTables[$alias])) {
                    $this->joins[] = "LEFT JOIN {$wpdb->users} AS {$alias} ON {$alias}.ID = p.post_author";
                    $addedJoinedTables[$alias] = true;
                }
            } else {
                // Post meta field — add a JOIN.
                $alias = $this->nextJoinAlias();
                $this->addMetaJoin($alias, $key);
                $columnMap[$key] = "{$alias}.meta_value";
            }
        }

        return $columnMap;
    }

    protected function getFrom(Query $query): string
    {
        global $wpdb;

        return "{$wpdb->posts} AS p";
    }

    protected function getJoins(): array
    {
        return $this->joins;
    }

    protected function compileScopeWhere(Query $query): array
    {
        $clauses = [];
        $params = [];

        // Post type scope.
        $postTypes = $query->source->scope['post_type'] ?? ['post'];
        if (!is_array($postTypes)) {
            $postTypes = [$postTypes];
        }
        $placeholders = implode(', ', array_fill(0, count($postTypes), '%s'));
        $clauses[] = "p.post_type IN ({$placeholders})";
        $params = array_merge($params, $postTypes);

        // Post status scope.
        $postStatuses = $query->source->scope['post_status'] ?? ['publish'];
        if (!is_array($postStatuses)) {
            $postStatuses = [$postStatuses];
        }
        $placeholders = implode(', ', array_fill(0, count($postStatuses), '%s'));
        $clauses[] = "p.post_status IN ({$placeholders})";
        $params = array_merge($params, $postStatuses);

        return [
            'clause' => implode(' AND ', $clauses),
            'params' => $params,
        ];
    }

    /**
     * Search post_title, post_content, and post_excerpt only.
     *
     * @inheritDoc
     */
    protected function compileSearch(string $search, array $columnMap): array
    {
        $escaped = '%' . SqlFilterCompiler::escapeLike($search) . '%';

        return [
            'clause' => '(p.post_title LIKE %s OR p.post_content LIKE %s OR p.post_excerpt LIKE %s)',
            'params' => [$escaped, $escaped, $escaped],
        ];
    }

    protected function estimateRowCount(Query $query): ?int
    {
        global $wpdb;

        $postTypes = $query->source->scope['post_type'] ?? ['post'];
        if (!is_array($postTypes)) {
            $postTypes = [$postTypes];
        }

        $placeholders = implode(', ', array_fill(0, count($postTypes), '%s'));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ({$placeholders}) AND post_status != 'trash'",
                ...$postTypes,
            ),
        );

        return $count !== null ? (int) $count : null;
    }

    protected function getResultSchema(WpdbCompiledQuery $compiled): array
    {
        $schema = [];

        foreach ($compiled->columnMap as $key => $expr) {
            if (in_array($key, self::DATETIME_COLUMNS, true)) {
                $schema[$key] = ColumnType::Datetime;
            } elseif (str_ends_with($key, '_bucket') && in_array(substr($key, 0, -7), self::DATETIME_COLUMNS, true)) {
                $schema[$key] = ColumnType::Datetime;
            } elseif (in_array($key, self::NUMERIC_COLUMNS, true)) {
                $schema[$key] = ColumnType::Float;
            } else {
                $schema[$key] = ColumnType::String;
            }
        }

        return $schema;
    }

    private function addMetaJoin(string $alias, string $metaKey): void
    {
        global $wpdb;

        $this->joins[] = "LEFT JOIN {$wpdb->postmeta} AS {$alias} ON {$alias}.post_id = p.ID AND {$alias}.meta_key = '" . esc_sql($metaKey) . "'";
    }

    /**
     * Collect all unique field keys referenced by the query.
     */
    private function collectFieldKeys(Query $query, BackendSchema $schema): array
    {
        // Browse mode: select all available fields from the schema.
        if ($query->type === QueryType::Browse) {
            $keys = $schema->fieldNames();

            if ($query->time !== null) {
                $keys[] = $query->time->field;
            }
            foreach ($query->orderBy as $order) {
                if ($schema->hasField($order->field)) {
                    $keys[] = $order->field;
                }
            }
            $this->collectConditionKeys($query->where, $keys);

            return array_unique($keys);
        }

        $keys = [];

        foreach ($query->dimensions as $dim) {
            $keys[] = $dim->field;
        }

        foreach ($query->metrics as $metric) {
            if ($metric->field !== null) {
                $keys[] = $metric->field;
            }
        }

        if ($query->time !== null) {
            $keys[] = $query->time->field;
        }

        foreach ($query->orderBy as $order) {
            if ($schema->hasField($order->field)) {
                $keys[] = $order->field;
            }
        }

        $this->collectConditionKeys($query->where, $keys);

        return array_unique($keys);
    }

    private function collectConditionKeys(?object $group, array &$keys): void
    {
        if ($group === null) {
            return;
        }

        if ($group instanceof \DataKit\DataViews\Query\ConditionGroup) {
            foreach ($group->conditions as $condition) {
                $this->collectConditionKeys($condition, $keys);
            }
        } elseif ($group instanceof \DataKit\DataViews\Query\Condition) {
            $keys[] = $group->field;
        }
    }
}
