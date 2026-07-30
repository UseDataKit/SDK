<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query\Backend\WordPress\GravityForms;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\Backend\WordPress\GravityForms\FormSchemaProvider;
use DataKit\DataViews\Query\Backend\WordPress\GravityForms\GravityFormsBackend;
use DataKit\DataViews\Query\Backend\WordPress\GravityForms\GravityFormsFormRepository;
use DataKit\DataViews\Query\Backend\WordPress\GravityForms\GravityFormsSchemaProvider;
use DataKit\DataViews\Query\Backend\WordPress\WpdbCompiledQuery;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\LogicOperator;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\Source;
use DataKit\DataViews\Query\TimeBucket;
use DataKit\DataViews\Query\TimeRange;
use PHPUnit\Framework\TestCase;

/**
 * Compilation of Gravity Forms queries that reference per-form fields.
 */
final class GravityFormsBackendQueryTest extends TestCase
{
    private GravityFormsBackend $backend;

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

        $this->backend = new GravityFormsBackend(new GravityFormsSchemaProvider($this->forms()));
    }

    public function test_the_default_schema_serves_per_form_fields(): void
    {
        $schema = $this->backend->describe(['form_ids' => [42]]);

        self::assertTrue($schema->hasField('field:3'), 'Without this the engine rejects every form field.');
        self::assertTrue($schema->hasField('entry_id'));
    }

    public function test_a_form_field_joins_entry_meta_on_a_literal_meta_key(): void
    {
        $compiled = $this->compile($this->aggregateBy('field:3'));

        self::assertCount(1, $compiled->joins);
        self::assertStringContainsString('LEFT JOIN wp_gf_entry_meta', $compiled->joins[0]);
        self::assertStringContainsString('.entry_id = e.id', $compiled->joins[0]);
        self::assertStringContainsString(".meta_key = '3'", $compiled->joins[0]);
        self::assertStringEndsWith('.meta_value', $compiled->columnMap['field:3']);
    }

    public function test_the_meta_key_literal_carries_no_unbound_placeholder(): void
    {
        // The executor runs the whole statement through wpdb::prepare() with
        // only the bound params, so a %s left in a JOIN is a placeholder count
        // mismatch rather than a value.
        $compiled = $this->compile($this->aggregateBy('field:3'));

        self::assertStringNotContainsString('%s', $compiled->joins[0]);
        self::assertStringNotContainsString('%d', $compiled->joins[0]);
    }

    public function test_a_sub_input_key_joins_its_own_meta_key(): void
    {
        $compiled = $this->compile($this->aggregateBy('field:1.3'));

        self::assertStringContainsString(".meta_key = '1.3'", $compiled->joins[0]);
    }

    public function test_a_key_that_cannot_be_a_meta_key_is_dropped(): void
    {
        $compiled = $this->compile($this->aggregateBy("field:3' OR 1=1 --"));

        self::assertSame([], $compiled->joins);
        self::assertSame([], $compiled->columnMap);
    }

    public function test_a_bare_numeric_key_compiles_without_a_type_error(): void
    {
        // A provider outside the SDK can still hand back "3". PHP casts that to
        // an int the moment it keys the column map, and every string helper in
        // the result-schema pass throws a TypeError on an int.
        $backend = new GravityFormsBackend($this->providerWithBareNumericKey());

        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('3')],
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $backend->compile($query, $backend->describe($query->source->scope));

        self::assertInstanceOf(WpdbCompiledQuery::class, $compiled);
        self::assertStringContainsString(".meta_key = '3'", $compiled->joins[0]);
        self::assertSame(
            [3 => ColumnType::String, 'total' => ColumnType::Integer],
            $compiled->outputSchema,
            'PHP hands back the int key it made; the compile must survive it.',
        );
    }

    public function test_system_columns_resolve_without_a_join(): void
    {
        $compiled = $this->compile($this->aggregateBy('payment_status'));

        self::assertSame('e.payment_status', $compiled->columnMap['payment_status']);
        self::assertSame([], $compiled->joins);
    }

    public function test_the_semantic_created_at_alias_maps_to_date_created(): void
    {
        $compiled = $this->compile($this->aggregateBy('created_at'));

        self::assertSame('e.date_created', $compiled->columnMap['created_at']);
    }

    public function test_form_title_joins_the_form_table(): void
    {
        $compiled = $this->compile($this->aggregateBy('form_title'));

        self::assertSame('gf_form.title', $compiled->columnMap['form_title']);
        self::assertStringContainsString('INNER JOIN wp_gf_form', $compiled->joins[0]);
    }

    public function test_a_filter_on_a_form_field_compiles_against_the_joined_value(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
            where: new ConditionGroup(LogicOperator::And, [
                new Condition('field:3', ComparisonOperator::Eq, '7'),
            ]),
        );

        $compiled = $this->compile($query);

        self::assertCount(1, $compiled->joins);
        self::assertStringContainsString('.meta_value', implode(' ', $compiled->where));
        self::assertContains('7', $compiled->params);
    }

    public function test_the_result_schema_types_form_fields_from_the_schema(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('field:3'), new SelectField('field:5')],
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $this->compile($query);

        self::assertSame(ColumnType::Float, $compiled->outputSchema['field:3']);
        self::assertSame(ColumnType::String, $compiled->outputSchema['field:5']);
    }

    public function test_a_time_bucket_alias_is_typed_as_a_datetime(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Aggregate,
            time: new TimeRange(field: 'date_created', grain: TimeBucket::Day),
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $compiled = $this->compile($query);

        self::assertSame(ColumnType::Datetime, $compiled->outputSchema['date_created_bucket']);
    }

    public function test_a_scoped_status_narrows_the_where_clause(): void
    {
        $query = new Query(
            source: new Source('gravity_forms', 'entries', ['form_ids' => [42], 'status' => ['spam']]),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $where = implode(' ', $this->compile($query)->where);

        self::assertStringContainsString("e.status IN ('spam')", $where);
    }

    public function test_an_unknown_scoped_status_falls_back_to_active(): void
    {
        $query = new Query(
            source: new Source('gravity_forms', 'entries', ['form_ids' => [42], 'status' => ["' OR 1=1 --"]]),
            type: QueryType::Aggregate,
            metrics: [new AggregateField(AggregateFunction::Count, null, 'total')],
        );

        $where = implode(' ', $this->compile($query)->where);

        self::assertStringNotContainsString('OR 1=1', $where);
        self::assertStringContainsString("e.status IN ('active')", $where);
    }

    public function test_browse_mode_selects_the_form_fields_too(): void
    {
        $query = new Query(
            source: $this->source(),
            type: QueryType::Browse,
        );

        $compiled = $this->compile($query);

        self::assertArrayHasKey('field:3', $compiled->columnMap);
        self::assertStringContainsString('e.id', implode(' ', $compiled->select));
    }

    private function source(): Source
    {
        return new Source('gravity_forms', 'entries', ['form_ids' => [42]]);
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
     * A provider that keys a form field the old way, by bare field ID.
     */
    private function providerWithBareNumericKey(): FormSchemaProvider
    {
        return new class implements FormSchemaProvider {
            public function describe(array $scope): BackendSchema
            {
                return new BackendSchema(
                    'gravity_forms',
                    'Gravity Forms Entries',
                    '',
                    [],
                    [new FieldSchema('3', 'Headcount', ColumnType::String, ComparisonOperator::cases())],
                );
            }
        };
    }

    private function forms(): GravityFormsFormRepository
    {
        return new class implements GravityFormsFormRepository {
            public function getForm(int $formId): ?array
            {
                if ($formId !== 42) {
                    return null;
                }

                return [
                    'id' => 42,
                    'title' => 'Signups',
                    'fields' => [
                        (object) [
                            'id' => 1,
                            'type' => 'name',
                            'label' => 'Your name',
                            'inputs' => [
                                ['id' => '1.3', 'label' => 'First'],
                                ['id' => '1.6', 'label' => 'Last'],
                            ],
                        ],
                        (object) ['id' => 3, 'type' => 'number', 'label' => 'Headcount'],
                        (object) [
                            'id' => 5,
                            'type' => 'select',
                            'label' => 'Colour',
                            'choices' => [
                                ['value' => 'red', 'text' => 'Red'],
                                ['value' => 'blue', 'text' => 'Blue'],
                            ],
                        ],
                    ],
                ];
            }
        };
    }
}
