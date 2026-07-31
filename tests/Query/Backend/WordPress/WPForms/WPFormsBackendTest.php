<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress\WPForms;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\Backend\WordPress\WPForms\WPFormsBackend;
use DataKit\DataViews\Query\Backend\WordPress\WPForms\WPFormsFormRepository;
use DataKit\DataViews\Query\Backend\WordPress\WpdbCompiledQuery;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\LogicOperator;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Exception\QueryValidationException;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\Source;
use DataKit\DataViews\Query\TimeBucket;
use DataKit\DataViews\Query\TimeRange;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the WPForms query backend.
 *
 * The table shape under test — `wpforms_entries` for system columns,
 * `wpforms_entry_fields` for per-field answers, `wpforms_entry_meta` for quiz
 * meta — is taken from GravityDash's own `WPFormsQueryCompiler`, not from an
 * installed copy of WPForms Pro. It matches what that compiler and the
 * plugin's Lite storage layer write. It has NOT been checked against a real
 * WPForms Pro install.
 */
final class WPFormsBackendTest extends TestCase
{
    private WPFormsBackend $backend;

    protected function setUp(): void
    {
        global $wpdb;

        $wpdb = new class {
            public string $prefix = 'wp_';

            public function prepare(string $query, ...$args): string
            {
                $i = 0;

                return preg_replace_callback('/%[sd]/', static function () use ($args, &$i) {
                    return "'" . ($args[$i++] ?? '') . "'";
                }, $query);
            }

            public function esc_like(string $text): string
            {
                return addcslashes($text, '_%\\');
            }
        };

        // A two-field form, one text and one number, plus quiz enabled.
        $this->backend = new WPFormsBackend(
            new class implements WPFormsFormRepository {
                public function getForm(int $formId): ?array
                {
                    if ($formId !== 42) {
                        return null;
                    }

                    return [
                        'id' => 42,
                        'settings' => ['quiz_enable' => '1'],
                        'fields' => [
                            ['id' => '1', 'type' => 'text', 'label' => 'Your name'],
                            ['id' => '3', 'type' => 'number', 'label' => 'Headcount'],
                            ['id' => '5', 'type' => 'html', 'label' => 'Filler'],
                            ['id' => '7', 'type' => 'payment-total', 'label' => 'Total'],
                        ],
                    ];
                }
            },
        );
    }

    // =====================================================================
    // Identity
    // =====================================================================

    public function test_source_type_is_wpforms(): void
    {
        self::assertSame('wpforms', $this->backend->sourceType());
    }

    public function test_source_factory_builds_a_scoped_source(): void
    {
        $source = WPFormsBackend::source([42], ['partial']);

        self::assertSame('wpforms', $source->type);
        self::assertSame('entries', $source->entity);
        self::assertSame([42], $source->scope['form_ids']);
        self::assertSame(['partial'], $source->scope['status']);
    }

    public function test_capabilities_cover_filters_aggregates_and_structure(): void
    {
        $caps = $this->backend->capabilities();

        self::assertContains(Capability::FilterEq, $caps);
        self::assertContains(Capability::FilterBetween, $caps);
        self::assertContains(Capability::AggSum, $caps);
        self::assertContains(Capability::AggCountDistinct, $caps);
        self::assertContains(Capability::GroupBy, $caps);
        self::assertContains(Capability::TimeBucket, $caps);
        self::assertContains(Capability::Search, $caps);
        self::assertContains(Capability::LimitOffset, $caps);
    }

    // =====================================================================
    // Schema
    // =====================================================================

    public function test_describe_without_a_form_scope_lists_entry_columns_only(): void
    {
        $schema = $this->backend->describe([]);

        self::assertTrue($schema->hasField('entry_id'));
        self::assertTrue($schema->hasField('form_id'));
        self::assertTrue($schema->hasField('date'));
        self::assertTrue($schema->hasField('status'));
        self::assertFalse($schema->hasField('field:1'));
    }

    public function test_describe_advertises_per_form_fields_keyed_with_the_field_prefix(): void
    {
        $schema = $this->backend->describe(['form_ids' => [42]]);

        self::assertTrue($schema->hasField('field:1'), 'Text field must be queryable.');
        self::assertTrue($schema->hasField('field:3'), 'Number field must be queryable.');
        self::assertSame('Your name', $schema->getField('field:1')->label);
    }

    public function test_describe_excludes_layout_only_field_types(): void
    {
        $schema = $this->backend->describe(['form_ids' => [42]]);

        self::assertFalse($schema->hasField('field:5'), 'An html field holds no answer.');
    }

    public function test_numeric_and_payment_fields_are_aggregatable(): void
    {
        $schema = $this->backend->describe(['form_ids' => [42]]);

        self::assertTrue($schema->getField('field:3')->aggregatable);
        self::assertTrue($schema->getField('field:7')->aggregatable);
        self::assertFalse($schema->getField('field:1')->aggregatable);
    }

    public function test_quiz_meta_fields_appear_only_when_the_form_enables_quiz(): void
    {
        self::assertTrue($this->backend->describe(['form_ids' => [42]])->hasField('quiz_outcome'));
        self::assertFalse($this->backend->describe([])->hasField('quiz_outcome'));
    }

    public function test_field_keys_stay_strings_so_php_cannot_cast_them_to_int(): void
    {
        $schema = $this->backend->describe(['form_ids' => [42]]);

        foreach ($schema->fieldNames() as $name) {
            self::assertIsString($name);
        }
    }

    public function test_date_carries_cross_source_aliases(): void
    {
        $schema = $this->backend->describe([]);

        self::assertTrue($schema->hasField('created_at'));
        self::assertTrue($schema->hasField('updated_at'));
    }

    // =====================================================================
    // Column mapping and JOINs
    // =====================================================================

    public function test_system_columns_resolve_without_a_join(): void
    {
        $compiled = $this->compile($this->aggregateBy('status'));

        self::assertSame('e.status', $compiled->columnMap['status']);
        self::assertSame([], $compiled->joins);
    }

    public function test_a_form_field_joins_wpforms_entry_fields_on_field_id(): void
    {
        $compiled = $this->compile($this->aggregateBy('field:3'));

        self::assertCount(1, $compiled->joins);
        self::assertStringContainsString('LEFT JOIN wp_wpforms_entry_fields', $compiled->joins[0]);
        self::assertStringContainsString('.entry_id = e.entry_id', $compiled->joins[0]);
        self::assertStringContainsString(".field_id = '3'", $compiled->joins[0]);
        self::assertStringEndsWith('.value', $compiled->columnMap['field:3']);
    }

    public function test_the_field_id_literal_carries_no_unbound_placeholder(): void
    {
        // The executor runs the compiled SQL through wpdb::prepare() with only
        // the bound params. A stray %s in a JOIN is a placeholder/argument
        // mismatch, not a working query.
        $compiled = $this->compile($this->aggregateBy('field:3'));

        self::assertStringNotContainsString('%s', $compiled->joins[0]);
        self::assertStringNotContainsString('%d', $compiled->joins[0]);
    }

    public function test_a_repeater_sub_field_id_is_accepted(): void
    {
        $compiled = $this->compile($this->aggregateBy('field:3.0'));

        self::assertStringContainsString(".field_id = '3.0'", $compiled->joins[0]);
    }

    public function test_a_field_key_that_cannot_be_a_field_id_is_dropped(): void
    {
        $query = $this->aggregateBy("field:3' OR 1=1 --");

        self::assertSame([], $this->columnMapFor($query), 'The key must not reach the column map.');

        // Absence from the map was only half the guarantee. The SELECT path
        // used to fall back to the raw field key, so the string this test
        // called "dropped" was still spliced into the statement as a bare SQL
        // expression. Compilation now refuses the unmapped key outright.
        $this->expectException(QueryValidationException::class);

        $this->backend->compile($query, $this->backend->describe($query->source->scope));
    }

    public function test_quiz_meta_joins_the_entry_meta_table_on_type(): void
    {
        $compiled = $this->compile($this->aggregateBy('quiz_outcome'));

        self::assertCount(1, $compiled->joins);
        self::assertStringContainsString('LEFT JOIN wp_wpforms_entry_meta', $compiled->joins[0]);
        self::assertStringContainsString(".type = 'quiz_outcome'", $compiled->joins[0]);
        self::assertStringEndsWith('.data', $compiled->columnMap['quiz_outcome']);
    }

    public function test_field_and_quiz_aliases_never_collide(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('field:3'), new SelectField('quiz_outcome')],
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $this->compile($query);
        $aliases = [];

        foreach ($compiled->joins as $join) {
            preg_match('/ AS (\w+) ON /', $join, $m);
            $aliases[] = $m[1];
        }

        self::assertCount(2, $aliases);
        self::assertSame($aliases, array_unique($aliases));
    }

    public function test_the_same_field_referenced_twice_joins_once(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('field:3')],
            metrics: [new AggregateField(AggregateFunction::Sum, 'field:3', 'total')],
        );

        self::assertCount(1, $this->compile($query)->joins);
    }

    public function test_join_count_is_capped(): void
    {
        // MySQL's planner degrades sharply past a few dozen EAV joins; the cap
        // is what stops a wide form from compiling an unrunnable query.
        $dimensions = [];

        for ($i = 1; $i <= 60; $i++) {
            $dimensions[] = new SelectField('field:' . $i);
        }

        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: $dimensions,
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        self::assertLessThanOrEqual(40, count($this->columnMapFor($query)));

        // The cap leaves the fields past it unmapped. Compilation used to fall
        // back to the raw key for those, emitting `field:41 AS ...` — a syntax
        // error, so the "unrunnable query" the cap exists to prevent is exactly
        // what it produced. Over the cap is now an explicit refusal.
        $this->expectException(QueryValidationException::class);

        $this->backend->compile($query, $this->backend->describe($query->source->scope));
    }

    // =====================================================================
    // Scope
    // =====================================================================

    public function test_form_scope_binds_ids_as_integers(): void
    {
        $compiled = $this->compile($this->aggregateBy('status'));

        self::assertStringContainsString('e.form_id IN (%d)', $compiled->where[0]);
        self::assertContains(42, $compiled->params);
    }

    public function test_trash_and_spam_are_excluded_when_no_status_is_scoped(): void
    {
        $compiled = $this->compile($this->aggregateBy('status'));
        $where = implode(' ', $compiled->where);

        self::assertStringContainsString("e.status NOT IN ('trash', 'spam')", $where);
    }

    public function test_an_explicit_status_scope_replaces_the_default(): void
    {
        $query = new Query(
            source: new Source('wpforms', 'entries', ['form_ids' => [42], 'status' => ['spam']]),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $this->compile($query);
        $where = implode(' ', $compiled->where);

        self::assertStringContainsString('e.status IN (%s)', $where);
        self::assertStringNotContainsString('NOT IN', $where);
        self::assertContains('spam', $compiled->params);
    }

    public function test_a_scoped_status_is_bound_never_interpolated(): void
    {
        $query = new Query(
            source: new Source('wpforms', 'entries', ['form_ids' => [42], 'status' => ["' OR 1=1 --"]]),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $this->compile($query);

        self::assertStringNotContainsString('OR 1=1', implode(' ', $compiled->where));
        self::assertContains("' OR 1=1 --", $compiled->params);
    }

    // =====================================================================
    // Filters, search, result schema
    // =====================================================================

    public function test_a_filter_on_a_form_field_compiles_against_the_joined_value(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
            where: new ConditionGroup(LogicOperator::And, [
                new Condition('field:1', ComparisonOperator::Eq, 'Ada'),
            ]),
        );

        $compiled = $this->compile($query);

        self::assertCount(1, $compiled->joins);
        self::assertStringContainsString('.value', implode(' ', $compiled->where));
        self::assertContains('Ada', $compiled->params);
    }

    public function test_search_targets_answer_values_rather_than_every_column(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('field:1')],
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
            search: 'ada',
        );

        $compiled = $this->compile($query);
        $where = implode(' ', $compiled->where);

        self::assertStringContainsString('LIKE %s', $where);
        self::assertContains('%ada%', $compiled->params);
    }

    public function test_result_schema_types_dates_numerics_and_answers(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('entry_id'), new SelectField('field:1')],
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $this->compile($query);
        $schema = $compiled->outputSchema;

        self::assertSame(ColumnType::Float, $schema['entry_id']);
        self::assertSame(ColumnType::String, $schema['field:1']);
        self::assertSame(ColumnType::Integer, $schema['total']);
    }

    public function test_a_time_bucket_alias_is_typed_as_a_datetime(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            time: new TimeRange(field: 'date', grain: TimeBucket::Day),
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $this->compile($query);

        self::assertSame(ColumnType::Datetime, $compiled->outputSchema['date_bucket']);
    }

    public function test_browse_mode_selects_entry_columns_without_a_field_map(): void
    {
        $query = new Query(
            source: new Source('wpforms', 'entries', ['form_ids' => [42]]),
            type: QueryType::Browse,
        );

        $compiled = $this->compile($query);
        $select = implode(' ', $compiled->select);

        self::assertStringContainsString('e.entry_id', $select);
        self::assertStringContainsString('e.date', $select);
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    private function source(): Source
    {
        return new Source('wpforms', 'entries', ['form_ids' => [42]]);
    }

    private function aggregateBy(string $field): Query
    {
        return new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: [new SelectField($field)],
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );
    }

    private function compile(Query $query): WpdbCompiledQuery
    {
        $schema = $this->backend->describe($query->source->scope);
        $compiled = $this->backend->compile($query, $schema);

        self::assertInstanceOf(WpdbCompiledQuery::class, $compiled);

        return $compiled;
    }

    /**
     * Build the column map for a query without compiling it.
     *
     * @return array<string, string>
     */
    private function columnMapFor(Query $query, ?object $backend = null): array
    {
        $backend ??= $this->backend;
        $schema = $backend->describe($query->source->scope);

        return (new \ReflectionMethod($backend, 'buildColumnMap'))->invoke($backend, $query, $schema);
    }
}
