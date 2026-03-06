<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\WordPressUsers;

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
use DataKit\DataViews\Query\Source;

/**
 * WordPress Users query backend.
 *
 * Queries wp_users with LEFT JOIN wp_usermeta per meta field.
 *
 * @since $ver$
 */
final class WordPressUsersBackend extends AbstractWpdbBackend
{
    private const USER_COLUMNS = [
        'user_id' => 'ID',
        'user_login' => 'user_login',
        'user_email' => 'user_email',
        'user_nicename' => 'user_nicename',
        'display_name' => 'display_name',
        'user_registered' => 'user_registered',
        'user_status' => 'user_status',
        'user_url' => 'user_url',
        // Semantic aliases.
        'created_at' => 'user_registered',
        'updated_at' => 'user_registered',
    ];

    private const COMMON_META_KEYS = [
        'first_name', 'last_name', 'nickname', 'description', 'locale',
    ];

    /** @var string[] Accumulated JOINs. */
    private array $joins = [];

    public static function isAvailable(): bool
    {
        return true;
    }

    public function sourceType(): string
    {
        return 'wordpress_users';
    }

    /**
     * Create a source for WordPress Users.
     *
     * @param array $scope Source-specific scope.
     */
    public static function source( array $scope = [] ): Source {
        return new Source( 'wordpress_users', 'users', $scope );
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
            Capability::TimeBucket, Capability::Search, Capability::OrderBy,
            Capability::LimitOffset,
        ];
    }

    public function describe(array $scope): BackendSchema
    {
        $allOps = ComparisonOperator::cases();

        $fields = [
            new FieldSchema('user_id', 'User ID', ColumnType::Integer, $allOps),
            new FieldSchema('user_login', 'Username', ColumnType::String, $allOps),
            new FieldSchema('user_email', 'Email', ColumnType::String, $allOps),
            new FieldSchema('user_nicename', 'Nicename', ColumnType::String, $allOps),
            new FieldSchema('display_name', 'Display Name', ColumnType::String, $allOps),
            new FieldSchema('user_registered', 'Registered', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
            new FieldSchema('user_status', 'Status', ColumnType::Integer, $allOps),
            new FieldSchema('user_url', 'Website URL', ColumnType::String, $allOps),
            new FieldSchema('first_name', 'First Name', ColumnType::String, $allOps),
            new FieldSchema('last_name', 'Last Name', ColumnType::String, $allOps),
            new FieldSchema('nickname', 'Nickname', ColumnType::String, $allOps),
            new FieldSchema('description', 'Bio', ColumnType::String, $allOps, sortable: false),
            new FieldSchema('locale', 'Locale', ColumnType::String, $allOps),
            // Semantic aliases.
            new FieldSchema('created_at', 'Created At', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
                description: 'Alias for user_registered.',
            ),
            new FieldSchema('updated_at', 'Updated At', ColumnType::Datetime, $allOps,
                timezone: 'utc',
                description: 'Alias for user_registered (users have no separate updated_at).',
            ),
        ];

        return new BackendSchema(
            'wordpress_users',
            'WordPress Users',
            'WordPress user accounts with profile metadata.',
            $this->capabilities(),
            $fields,
        );
    }

    protected function buildColumnMap(Query $query, BackendSchema $schema): array
    {
        $this->joins = [];
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            if (isset(self::USER_COLUMNS[$key])) {
                $columnMap[$key] = 'u.' . self::USER_COLUMNS[$key];
            } else {
                // Usermeta field
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

        return "{$wpdb->users} AS u";
    }

    protected function getJoins(): array
    {
        return $this->joins;
    }

    protected function compileScopeWhere(Query $query): array
    {
        global $wpdb;

        $role = $query->source->scope['role'] ?? '';

        if ($role === '') {
            return ['clause' => '', 'params' => []];
        }

        $capKey = $wpdb->prefix . 'capabilities';
        $escaped = '%"' . SqlFilterCompiler::escapeLike($role) . '"%';

        return [
            'clause' => "u.ID IN (SELECT um_role.user_id FROM {$wpdb->usermeta} AS um_role WHERE um_role.meta_key = %s AND um_role.meta_value LIKE %s)",
            'params' => [$capKey, $escaped],
        ];
    }

    protected function estimateRowCount(Query $query): ?int
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}");

        return $count !== null ? (int) $count : null;
    }

    protected function getResultSchema(WpdbCompiledQuery $compiled): array
    {
        $schema = [];

        foreach ($compiled->columnMap as $key => $expr) {
            $schema[$key] = match (true) {
                $key === 'user_id' || $key === 'user_status' => ColumnType::Integer,
                $key === 'user_registered' || $key === 'created_at' || $key === 'updated_at' => ColumnType::Datetime,
                str_ends_with($key, '_bucket') => ColumnType::Datetime,
                default => ColumnType::String,
            };
        }

        return $schema;
    }

    private function addMetaJoin(string $alias, string $metaKey): void
    {
        global $wpdb;

        $this->joins[] = "LEFT JOIN {$wpdb->usermeta} AS {$alias} ON {$alias}.user_id = u.ID AND {$alias}.meta_key = %s";
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
}
