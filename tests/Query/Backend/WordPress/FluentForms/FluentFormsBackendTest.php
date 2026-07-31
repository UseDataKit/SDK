<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress\FluentForms;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\Backend\WordPress\FluentForms\FluentFormsBackend;
use DataKit\DataViews\Query\Backend\WordPress\FluentForms\FluentFormsFormRepository;
use DataKit\DataViews\Query\Backend\WordPress\WpdbCompiledQuery;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\LogicOperator;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\Source;
use DataKit\DataViews\Query\TimeBucket;
use DataKit\DataViews\Query\TimeRange;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Fluent Forms query backend.
 *
 * The table shape under test — `fluentform_submissions` for system columns,
 * `fluentform_entry_details` (`field_name` + `sub_field_name` + `field_value`)
 * for per-field answers — was read from a live Fluent Forms install and from
 * `SubmissionService::recordEntryDetails()` in the Fluent Forms plugin, which
 * writes one row per scalar answer and one row per (field, sub-key) for array
 * answers.
 */
final class FluentFormsBackendTest extends TestCase
{
    private FluentFormsBackend $backend;

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

        // The stock "Contact Form Demo" shape plus a number and a checkbox.
        $this->backend = new FluentFormsBackend(
            new class implements FluentFormsFormRepository {
                public function getFields(int $formId): array
                {
                    if ($formId !== 7) {
                        return [];
                    }

                    return [
                        [
                            'name' => 'names',
                            'element' => 'input_name',
                            'label' => 'Name',
                            'children' => [
                                'first_name' => 'First Name',
                                'last_name' => 'Last Name',
                            ],
                        ],
                        ['name' => 'email', 'element' => 'input_email', 'label' => 'Email', 'children' => []],
                        ['name' => 'headcount', 'element' => 'input_number', 'label' => 'Headcount', 'children' => []],
                        ['name' => 'topics', 'element' => 'input_checkbox', 'label' => 'Topics', 'children' => []],
                        ['name' => 'bad name!', 'element' => 'input_text', 'label' => 'Broken', 'children' => []],
                    ];
                }
            },
        );
    }

    // =====================================================================
    // Identity
    // =====================================================================

    public function test_source_type_is_fluent_forms(): void
    {
        self::assertSame('fluent_forms', $this->backend->sourceType());
    }

    public function test_source_factory_builds_a_scoped_source(): void
    {
        $source = FluentFormsBackend::source([7], ['read']);

        self::assertSame('fluent_forms', $source->type);
        self::assertSame('submissions', $source->entity);
        self::assertSame([7], $source->scope['form_ids']);
        self::assertSame(['read'], $source->scope['status']);
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

    public function test_describe_without_a_form_scope_lists_submission_columns_only(): void
    {
        $schema = $this->backend->describe([]);

        self::assertTrue($schema->hasField('id'));
        self::assertTrue($schema->hasField('form_id'));
        self::assertTrue($schema->hasField('created_at'));
        self::assertTrue($schema->hasField('status'));
        self::assertTrue($schema->hasField('payment_total'));
        self::assertFalse($schema->hasField('field:email'));
    }

    public function test_describe_advertises_per_form_fields_keyed_with_the_field_prefix(): void
    {
        $schema = $this->backend->describe(['form_ids' => [7]]);

        self::assertTrue($schema->hasField('field:email'), 'Email field must be queryable.');
        self::assertTrue($schema->hasField('field:headcount'), 'Number field must be queryable.');
        self::assertSame('Email', $schema->getField('field:email')->label);
    }

    public function test_a_container_field_is_advertised_through_its_sub_inputs_only(): void
    {
        $schema = $this->backend->describe(['form_ids' => [7]]);

        self::assertTrue($schema->hasField('field:names.first_name'));
        self::assertTrue($schema->hasField('field:names.last_name'));
        self::assertFalse(
            $schema->hasField('field:names'),
            'The bare container has no fluentform_entry_details row and must not be advertised.',
        );
        self::assertSame('Name: First Name', $schema->getField('field:names.first_name')->label);
    }

    public function test_a_field_name_that_cannot_be_inlined_is_not_advertised(): void
    {
        $schema = $this->backend->describe(['form_ids' => [7]]);

        self::assertFalse($schema->hasField('field:bad name!'));
    }

    public function test_numeric_fields_are_aggregatable(): void
    {
        $schema = $this->backend->describe(['form_ids' => [7]]);

        self::assertTrue($schema->getField('field:headcount')->aggregatable);
        self::assertFalse($schema->getField('field:email')->aggregatable);
    }

    public function test_cross_source_semantic_keys_resolve_natively(): void
    {
        $schema = $this->backend->describe([]);

        // `created_at` / `updated_at` are Fluent Forms' real column names,
        // so a spec written against another source's alias still resolves.
        self::assertTrue($schema->hasField('created_at'));
        self::assertTrue($schema->hasField('updated_at'));
    }

    // =====================================================================
    // Column mapping and JOINs
    // =====================================================================

    public function test_system_columns_resolve_without_a_join(): void
    {
        $compiled = $this->compile($this->aggregateBy('status'));

        self::assertSame('s.status', $compiled->columnMap['status']);
        self::assertSame([], $compiled->joins);
    }

    public function test_a_single_value_field_joins_on_the_name_with_an_empty_sub_field(): void
    {
        $compiled = $this->compile($this->aggregateBy('field:email'));

        self::assertCount(1, $compiled->joins);
        self::assertStringContainsString('LEFT JOIN wp_fluentform_entry_details', $compiled->joins[0]);
        self::assertStringContainsString('.submission_id = s.id', $compiled->joins[0]);
        self::assertStringContainsString(".field_name = 'email'", $compiled->joins[0]);
        self::assertStringContainsString(".sub_field_name = ''", $compiled->joins[0]);
        self::assertStringEndsWith('.field_value', $compiled->columnMap['field:email']);
    }

    public function test_a_sub_field_key_joins_on_the_name_and_sub_field_pair(): void
    {
        $compiled = $this->compile($this->aggregateBy('field:names.first_name'));

        self::assertCount(1, $compiled->joins);
        self::assertStringContainsString(".field_name = 'names'", $compiled->joins[0]);
        self::assertStringContainsString(".sub_field_name = 'first_name'", $compiled->joins[0]);
    }

    public function test_a_multi_value_field_joins_without_pinning_the_sub_field(): void
    {
        // Fluent Forms stores one row per selected checkbox value with
        // numeric sub_field_name keys; pinning '' would match nothing.
        $compiled = $this->compile($this->aggregateBy('field:topics'));

        self::assertCount(1, $compiled->joins);
        self::assertStringContainsString(".field_name = 'topics'", $compiled->joins[0]);
        self::assertStringNotContainsString('sub_field_name', $compiled->joins[0]);
    }

    public function test_the_join_literal_carries_no_unbound_placeholder(): void
    {
        $compiled = $this->compile($this->aggregateBy('field:email'));

        self::assertStringNotContainsString('%s', $compiled->joins[0]);
        self::assertStringNotContainsString('%d', $compiled->joins[0]);
    }

    public function test_a_field_key_that_cannot_be_a_field_name_is_dropped(): void
    {
        $compiled = $this->compile($this->aggregateBy("field:email' OR 1=1 --"));

        self::assertSame([], $compiled->joins);
        self::assertSame([], $compiled->columnMap);
    }

    public function test_a_sub_field_key_with_an_injection_sub_segment_is_dropped(): void
    {
        $compiled = $this->compile($this->aggregateBy("field:names.first' --"));

        self::assertSame([], $compiled->joins);
        self::assertSame([], $compiled->columnMap);
    }

    public function test_the_same_field_referenced_twice_joins_once(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('field:headcount')],
            metrics: [new AggregateField(AggregateFunction::Sum, 'field:headcount', 'total')],
        );

        self::assertCount(1, $this->compile($query)->joins);
    }

    public function test_join_count_is_capped(): void
    {
        $repository = new class implements FluentFormsFormRepository {
            public function getFields(int $formId): array
            {
                $fields = [];

                for ($i = 1; $i <= 60; $i++) {
                    $fields[] = [
                        'name' => 'input_' . $i,
                        'element' => 'input_text',
                        'label' => 'Input ' . $i,
                        'children' => [],
                    ];
                }

                return $fields;
            }
        };

        $backend = new FluentFormsBackend($repository);
        $dimensions = [];

        for ($i = 1; $i <= 60; $i++) {
            $dimensions[] = new SelectField('field:input_' . $i);
        }

        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: $dimensions,
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $schema = $backend->describe($query->source->scope);
        $compiled = $backend->compile($query, $schema);

        self::assertInstanceOf(WpdbCompiledQuery::class, $compiled);
        self::assertLessThanOrEqual(40, count($compiled->joins));
    }

    // =====================================================================
    // Scope
    // =====================================================================

    public function test_form_scope_binds_ids_as_integers(): void
    {
        $compiled = $this->compile($this->aggregateBy('status'));

        self::assertStringContainsString('s.form_id IN (%d)', $compiled->where[0]);
        self::assertContains(7, $compiled->params);
    }

    public function test_trashed_and_spam_are_excluded_when_no_status_is_scoped(): void
    {
        $compiled = $this->compile($this->aggregateBy('status'));
        $where = implode(' ', $compiled->where);

        self::assertStringContainsString("s.status NOT IN ('trashed', 'spam')", $where);
    }

    public function test_an_explicit_status_scope_replaces_the_default(): void
    {
        $query = new Query(
            source: new Source('fluent_forms', 'submissions', ['form_ids' => [7], 'status' => ['spam']]),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $this->compile($query);
        $where = implode(' ', $compiled->where);

        self::assertStringContainsString('s.status IN (%s)', $where);
        self::assertStringNotContainsString('NOT IN', $where);
        self::assertContains('spam', $compiled->params);
    }

    public function test_a_scoped_status_is_bound_never_interpolated(): void
    {
        $query = new Query(
            source: new Source('fluent_forms', 'submissions', ['form_ids' => [7], 'status' => ["' OR 1=1 --"]]),
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
                new Condition('field:email', ComparisonOperator::Eq, 'ada@example.com'),
            ]),
        );

        $compiled = $this->compile($query);

        self::assertCount(1, $compiled->joins);
        self::assertStringContainsString('.field_value', implode(' ', $compiled->where));
        self::assertContains('ada@example.com', $compiled->params);
    }

    public function test_search_targets_answer_values_rather_than_every_column(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('field:email')],
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
            dimensions: [new SelectField('id'), new SelectField('field:email')],
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $this->compile($query);
        $schema = $compiled->outputSchema;

        self::assertSame(ColumnType::Float, $schema['id']);
        self::assertSame(ColumnType::String, $schema['field:email']);
        self::assertSame(ColumnType::Integer, $schema['total']);
    }

    public function test_a_time_bucket_alias_is_typed_as_a_datetime(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            time: new TimeRange(field: 'created_at', grain: TimeBucket::Day),
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $this->compile($query);

        self::assertSame(ColumnType::Datetime, $compiled->outputSchema['created_at_bucket']);
    }

    public function test_browse_mode_selects_submission_columns_without_a_field_map(): void
    {
        $query = new Query(
            source: new Source('fluent_forms', 'submissions', ['form_ids' => []]),
            type: QueryType::Browse,
        );

        $compiled = $this->compile($query);
        $select = implode(' ', $compiled->select);

        self::assertStringContainsString('s.id', $select);
        self::assertStringContainsString('s.created_at', $select);
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    private function source(): Source
    {
        return new Source('fluent_forms', 'submissions', ['form_ids' => [7]]);
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
}
