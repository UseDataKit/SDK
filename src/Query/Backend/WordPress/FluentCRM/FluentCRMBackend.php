<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\FluentCRM;

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
 * FluentCRM query backend supporting 3 entities: contacts, emails, campaigns.
 *
 * Queries FluentCRM's custom tables: fc_subscribers, fc_campaign_emails, fc_campaigns.
 *
 * @since $ver$
 */
final class FluentCRMBackend extends AbstractWpdbBackend
{
    /**
     * Contact columns on {prefix}fc_subscribers.
     *
     * Maps spec field keys to actual database column names.
     * The primary key is exposed as 'subscriber_id' to avoid
     * ambiguity with other entities' 'id' columns.
     *
     * @var array<string, string>
     */
    private const CONTACT_COLUMNS = [
        'subscriber_id' => 'id',
        'email'         => 'email',
        'first_name'    => 'first_name',
        'last_name'     => 'last_name',
        'status'        => 'status',
        'contact_type'  => 'contact_type',
        'created_at'    => 'created_at',
        'last_activity' => 'last_activity',
        'total_points'  => 'total_points',
        'country'       => 'country',
        'city'          => 'city',
    ];

    /**
     * Campaign email columns on {prefix}fc_campaign_emails.
     *
     * @var array<string, string>
     */
    private const EMAIL_COLUMNS = [
        'id'            => 'id',
        'subscriber_id' => 'subscriber_id',
        'campaign_id'   => 'campaign_id',
        'email_address' => 'email_address',
        'email_type'    => 'email_type',
        'status'        => 'status',
        'is_open'       => 'is_open',
        'created_at'    => 'created_at',
    ];

    /**
     * Campaign columns on {prefix}fc_campaigns.
     *
     * @var array<string, string>
     */
    private const CAMPAIGN_COLUMNS = [
        'id'            => 'id',
        'title'         => 'title',
        'status'        => 'status',
        'email_subject' => 'email_subject',
        'created_at'    => 'created_at',
        'scheduled_at'  => 'scheduled_at',
    ];

    /**
     * Accumulated JOIN clauses built during column map construction.
     *
     * @var string[]
     */
    private array $joins = [];

    /**
     * Check whether FluentCRM is active in the current environment.
     *
     * @return bool True if FluentCRM's main plugin class exists.
     */
    public static function isAvailable(): bool
    {
        return defined('FLUENTCRM');
    }

    /**
     * {@inheritDoc}
     */
    public function sourceType(): string
    {
        return 'fluentcrm';
    }

    /**
     * Create a source for FluentCRM data.
     *
     * @param string $entity Entity name: 'contacts', 'emails', or 'campaigns' (default: 'contacts').
     * @param array  $scope  Source-specific scope (e.g. ['status' => 'subscribed']).
     *
     * @return Source
     */
    public static function source(string $entity = 'contacts', array $scope = []): Source
    {
        return new Source('fluentcrm', $entity, $scope);
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
            Capability::TimeBucket, Capability::Search, Capability::OrderBy,
            Capability::LimitOffset,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function describe(array $scope): BackendSchema
    {
        $entity = $scope['entity'] ?? 'contacts';
        $allOps = ComparisonOperator::cases();

        $fields = match ($entity) {
            'contacts'  => $this->describeContactsFields($allOps),
            'emails'    => $this->describeEmailsFields($allOps),
            'campaigns' => $this->describeCampaignsFields($allOps),
            default     => [],
        };

        return new BackendSchema(
            'fluentcrm',
            'FluentCRM ' . ucfirst($entity),
            "FluentCRM {$entity} data.",
            $this->capabilities(),
            $fields,
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function buildColumnMap(Query $query, BackendSchema $schema): array
    {
        $this->joins = [];
        $entity = $this->resolveEntity($query);

        return match ($entity) {
            'contacts'  => $this->buildContactsColumnMap($query, $schema),
            'emails'    => $this->buildEmailsColumnMap($query, $schema),
            'campaigns' => $this->buildCampaignsColumnMap($query, $schema),
            default     => [],
        };
    }

    /**
     * {@inheritDoc}
     */
    protected function getFrom(Query $query): string
    {
        $entity = $this->resolveEntity($query);

        return match ($entity) {
            'contacts'  => $this->getTable('fc_subscribers') . ' AS s',
            'emails'    => $this->getTable('fc_campaign_emails') . ' AS e',
            'campaigns' => $this->getTable('fc_campaigns') . ' AS c',
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
     * {@inheritDoc}
     */
    protected function compileScopeWhere(Query $query): array
    {
        // FluentCRM entities do not require additional scope constraints
        // beyond what the user provides via filters. Each table stores
        // only its own entity type, so no type-column filtering is needed.
        return ['clause' => '', 'params' => []];
    }

    /**
     * {@inheritDoc}
     */
    protected function estimateRowCount(Query $query): ?int
    {
        global $wpdb;

        $entity = $this->resolveEntity($query);

        $table = match ($entity) {
            'contacts'  => $this->getTable('fc_subscribers'),
            'emails'    => $this->getTable('fc_campaign_emails'),
            'campaigns' => $this->getTable('fc_campaigns'),
            default     => null,
        };

        if ($table === null) {
            return null;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is built from known constants.
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        return $count !== null ? (int) $count : null;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, ColumnType>
     */
    protected function getResultSchema(WpdbCompiledQuery $compiled): array
    {
        $schema = [];

        foreach ($compiled->columnMap as $key => $expr) {
            $schema[$key] = match (true) {
                $key === 'subscriber_id'
                    || $key === 'id'
                    || $key === 'campaign_id'
                    || $key === 'is_open'
                    || $key === 'total_points' => ColumnType::Integer,
                $key === 'created_at'
                    || $key === 'last_activity'
                    || $key === 'scheduled_at'
                    || str_ends_with($key, '_bucket') => ColumnType::Datetime,
                default => ColumnType::String,
            };
        }

        return $schema;
    }

    // --- Contacts ---

    /**
     * Build the column map for contact queries.
     *
     * @param Query         $query  The query being compiled.
     * @param BackendSchema $schema The backend schema.
     *
     * @return array<string, string>
     */
    private function buildContactsColumnMap(Query $query, BackendSchema $schema): array
    {
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            if (isset(self::CONTACT_COLUMNS[$key])) {
                $columnMap[$key] = 's.' . self::CONTACT_COLUMNS[$key];
            }
        }

        return $columnMap;
    }

    /**
     * Describe all available contact fields.
     *
     * @param ComparisonOperator[] $allOps All available comparison operators.
     *
     * @return FieldSchema[]
     */
    private function describeContactsFields(array $allOps): array
    {
        return [
            new FieldSchema('subscriber_id', 'Subscriber ID', ColumnType::Integer, $allOps),
            new FieldSchema('email', 'Email', ColumnType::String, $allOps),
            new FieldSchema('first_name', 'First Name', ColumnType::String, $allOps),
            new FieldSchema('last_name', 'Last Name', ColumnType::String, $allOps),
            new FieldSchema('status', 'Status', ColumnType::String, $allOps,
                enumValues: [
                    'subscribed'   => 'Subscribed',
                    'pending'      => 'Pending',
                    'unsubscribed' => 'Unsubscribed',
                    'bounced'      => 'Bounced',
                    'complained'   => 'Complained',
                ],
            ),
            new FieldSchema('contact_type', 'Contact Type', ColumnType::String, $allOps),
            new FieldSchema('created_at', 'Created At', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
            new FieldSchema('last_activity', 'Last Activity', ColumnType::Datetime, $allOps,
                timezone: 'utc',
            ),
            new FieldSchema('total_points', 'Total Points', ColumnType::Integer, $allOps,
                aggregatable: true,
            ),
            new FieldSchema('country', 'Country', ColumnType::String, $allOps),
            new FieldSchema('city', 'City', ColumnType::String, $allOps),
        ];
    }

    // --- Emails ---

    /**
     * Build the column map for campaign email queries.
     *
     * @param Query         $query  The query being compiled.
     * @param BackendSchema $schema The backend schema.
     *
     * @return array<string, string>
     */
    private function buildEmailsColumnMap(Query $query, BackendSchema $schema): array
    {
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            if (isset(self::EMAIL_COLUMNS[$key])) {
                $columnMap[$key] = 'e.' . self::EMAIL_COLUMNS[$key];
            }
        }

        return $columnMap;
    }

    /**
     * Describe all available campaign email fields.
     *
     * @param ComparisonOperator[] $allOps All available comparison operators.
     *
     * @return FieldSchema[]
     */
    private function describeEmailsFields(array $allOps): array
    {
        return [
            new FieldSchema('id', 'Email ID', ColumnType::Integer, $allOps),
            new FieldSchema('subscriber_id', 'Subscriber ID', ColumnType::Integer, $allOps),
            new FieldSchema('campaign_id', 'Campaign ID', ColumnType::Integer, $allOps),
            new FieldSchema('email_address', 'Email Address', ColumnType::String, $allOps),
            new FieldSchema('email_type', 'Email Type', ColumnType::String, $allOps),
            new FieldSchema('status', 'Status', ColumnType::String, $allOps,
                enumValues: [
                    'sent'    => 'Sent',
                    'failed'  => 'Failed',
                    'bounced' => 'Bounced',
                    'opened'  => 'Opened',
                    'clicked' => 'Clicked',
                ],
            ),
            new FieldSchema('is_open', 'Is Open', ColumnType::Integer, $allOps,
                aggregatable: true,
                description: 'Whether the email was opened (0 or 1).',
            ),
            new FieldSchema('created_at', 'Created At', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
        ];
    }

    // --- Campaigns ---

    /**
     * Build the column map for campaign queries.
     *
     * @param Query         $query  The query being compiled.
     * @param BackendSchema $schema The backend schema.
     *
     * @return array<string, string>
     */
    private function buildCampaignsColumnMap(Query $query, BackendSchema $schema): array
    {
        $columnMap = [];
        $fieldKeys = $this->collectAllFieldKeys($query, $schema);

        foreach ($fieldKeys as $key) {
            if (isset(self::CAMPAIGN_COLUMNS[$key])) {
                $columnMap[$key] = 'c.' . self::CAMPAIGN_COLUMNS[$key];
            }
        }

        return $columnMap;
    }

    /**
     * Describe all available campaign fields.
     *
     * @param ComparisonOperator[] $allOps All available comparison operators.
     *
     * @return FieldSchema[]
     */
    private function describeCampaignsFields(array $allOps): array
    {
        return [
            new FieldSchema('id', 'Campaign ID', ColumnType::Integer, $allOps),
            new FieldSchema('title', 'Title', ColumnType::String, $allOps),
            new FieldSchema('status', 'Status', ColumnType::String, $allOps,
                enumValues: [
                    'draft'     => 'Draft',
                    'working'   => 'Working',
                    'scheduled' => 'Scheduled',
                    'archived'  => 'Archived',
                ],
            ),
            new FieldSchema('email_subject', 'Email Subject', ColumnType::String, $allOps),
            new FieldSchema('created_at', 'Created At', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
            ),
            new FieldSchema('scheduled_at', 'Scheduled At', ColumnType::Datetime, $allOps,
                timezone: 'utc',
            ),
        ];
    }

    // --- Helpers ---

    /**
     * Resolve the entity name from the query's source.
     *
     * Checks both the scope array and the entity property for backward
     * compatibility. Defaults to 'contacts' if neither is set.
     *
     * @param Query $query The query to resolve the entity from.
     *
     * @return string The entity name.
     */
    private function resolveEntity(Query $query): string
    {
        return $query->source->scope['entity'] ?? $query->source->entity ?: 'contacts';
    }

    /**
     * Get a fully-qualified FluentCRM table name with the WordPress table prefix.
     *
     * @param string $table The unprefixed FluentCRM table name (e.g. 'fc_subscribers').
     *
     * @return string The prefixed table name.
     */
    private function getTable(string $table): string
    {
        global $wpdb;

        return $wpdb->prefix . $table;
    }

    /**
     * Collect all field keys referenced in the query.
     *
     * In browse mode, selects all fields from the schema. In aggregate mode,
     * collects only fields referenced by dimensions, metrics, time, ordering,
     * and conditions.
     *
     * @param Query         $query  The query to inspect.
     * @param BackendSchema $schema The backend schema.
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
     * @param object   $group The condition or condition group.
     * @param string[] $keys  Collected keys (passed by reference).
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
}
