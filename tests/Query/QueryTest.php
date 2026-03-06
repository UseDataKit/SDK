<?php

declare(strict_types=1);

namespace DataKit\DataViews\Tests\Query;

use DataKit\DataViews\DataView\Operator;
use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Exception\QueryValidationException;
use DataKit\DataViews\Query\Limit;
use DataKit\DataViews\Query\LogicOperator;
use DataKit\DataViews\Query\OrderBy;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\SortDirection;
use DataKit\DataViews\Query\Source;
use DataKit\DataViews\Query\TimeBucket;
use DataKit\DataViews\Query\TimePreset;
use DataKit\DataViews\Query\TimeRange;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Phase 1 Query value objects.
 *
 * @since $ver$
 */
final class QueryTest extends TestCase
{
    // --- Enum tests ---

    public function test_query_type_enum(): void
    {
        self::assertSame('browse', QueryType::Browse->value);
        self::assertSame('aggregate', QueryType::Aggregate->value);
        self::assertSame(QueryType::Browse, QueryType::from('browse'));
    }

    public function test_aggregate_function_enum(): void
    {
        self::assertCount(6, AggregateFunction::cases());
        self::assertSame('count_distinct', AggregateFunction::CountDistinct->value);
    }

    public function test_time_bucket_enum(): void
    {
        self::assertCount(6, TimeBucket::cases());
        self::assertSame('quarter', TimeBucket::Quarter->value);
    }

    public function test_column_type_enum(): void
    {
        self::assertCount(5, ColumnType::cases());
        self::assertSame('datetime', ColumnType::Datetime->value);
    }

    public function test_sort_direction_enum(): void
    {
        self::assertSame('asc', SortDirection::Asc->value);
        self::assertSame('desc', SortDirection::Desc->value);
    }

    public function test_logic_operator_enum(): void
    {
        self::assertSame('and', LogicOperator::And->value);
        self::assertSame('or', LogicOperator::Or->value);
    }

    // --- ComparisonOperator bridge tests ---

    public function test_comparison_operator_from_operator_is(): void
    {
        self::assertSame(ComparisonOperator::Eq, ComparisonOperator::fromOperator(Operator::is()));
    }

    public function test_comparison_operator_from_operator_is_not(): void
    {
        self::assertSame(ComparisonOperator::Neq, ComparisonOperator::fromOperator(Operator::isNot()));
    }

    public function test_comparison_operator_from_operator_is_any(): void
    {
        self::assertSame(ComparisonOperator::In, ComparisonOperator::fromOperator(Operator::isAny()));
    }

    public function test_comparison_operator_from_operator_is_none(): void
    {
        self::assertSame(ComparisonOperator::NotIn, ComparisonOperator::fromOperator(Operator::isNone()));
    }

    public function test_comparison_operator_from_operator_is_all_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no query-layer equivalent');
        ComparisonOperator::fromOperator(Operator::isAll());
    }

    public function test_comparison_operator_from_operator_is_not_all_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ComparisonOperator::fromOperator(Operator::isNotAll());
    }

    public function test_comparison_operator_to_operator_roundtrip(): void
    {
        self::assertSame('is', (string) ComparisonOperator::Eq->toOperator());
        self::assertSame('isNot', (string) ComparisonOperator::Neq->toOperator());
        self::assertSame('isAny', (string) ComparisonOperator::In->toOperator());
        self::assertSame('isNone', (string) ComparisonOperator::NotIn->toOperator());
    }

    public function test_comparison_operator_to_operator_returns_null_for_query_only(): void
    {
        self::assertNull(ComparisonOperator::Gt->toOperator());
        self::assertNull(ComparisonOperator::Contains->toOperator());
        self::assertNull(ComparisonOperator::Between->toOperator());
        self::assertNull(ComparisonOperator::IsEmpty->toOperator());
    }

    // --- Value object tests ---

    public function test_source_serialization(): void
    {
        $source = new Source('test_backend', 'items', ['ids' => [1, 2]]);
        $array = $source->toArray();

        self::assertSame('test_backend', $array['type']);
        self::assertSame('items', $array['entity']);
        self::assertSame([1, 2], $array['scope']['ids']);

        $restored = Source::fromArray($array);
        self::assertSame($source->type, $restored->type);
        self::assertSame($source->entity, $restored->entity);
        self::assertSame($source->scope, $restored->scope);
    }

    public function test_source_minimal(): void
    {
        $source = new Source('array');
        $array = $source->toArray();

        self::assertSame(['type' => 'array'], $array);
    }

    public function test_select_field(): void
    {
        $field = new SelectField('status', 'entry_status');
        self::assertSame('entry_status', $field->outputName());
        self::assertSame(['field' => 'status', 'alias' => 'entry_status'], $field->toArray());

        $noAlias = new SelectField('status');
        self::assertSame('status', $noAlias->outputName());
    }

    public function test_aggregate_field(): void
    {
        $field = new AggregateField(AggregateFunction::Sum, 'amount', 'total');
        self::assertSame('total', $field->outputName());

        $noAlias = new AggregateField(AggregateFunction::Count);
        self::assertSame('count_*', $noAlias->outputName());

        $restored = AggregateField::fromArray($field->toArray());
        self::assertSame($field->function, $restored->function);
        self::assertSame($field->field, $restored->field);
        self::assertSame($field->alias, $restored->alias);
    }

    public function test_condition_eq(): void
    {
        $c = new Condition('status', ComparisonOperator::Eq, 'active');
        self::assertSame(['field' => 'status', 'operator' => 'eq', 'value' => 'active'], $c->toArray());
    }

    public function test_condition_between_valid(): void
    {
        $c = new Condition('amount', ComparisonOperator::Between, [10, 100]);
        self::assertSame([10, 100], $c->value);
    }

    public function test_condition_between_invalid(): void
    {
        $this->expectException(QueryValidationException::class);
        new Condition('amount', ComparisonOperator::Between, [10]);
    }

    public function test_condition_in_empty_array(): void
    {
        $this->expectException(QueryValidationException::class);
        new Condition('status', ComparisonOperator::In, []);
    }

    public function test_condition_is_empty_requires_null(): void
    {
        $c = new Condition('name', ComparisonOperator::IsEmpty, null);
        self::assertNull($c->value);
    }

    public function test_condition_is_empty_rejects_value(): void
    {
        $this->expectException(QueryValidationException::class);
        new Condition('name', ComparisonOperator::IsEmpty, 'oops');
    }

    public function test_condition_eq_rejects_array(): void
    {
        $this->expectException(QueryValidationException::class);
        new Condition('status', ComparisonOperator::Eq, ['active']);
    }

    public function test_condition_serialization_roundtrip(): void
    {
        $c = new Condition('status', ComparisonOperator::In, ['active', 'pending']);
        $restored = Condition::fromArray($c->toArray());

        self::assertSame($c->field, $restored->field);
        self::assertSame($c->operator, $restored->operator);
        self::assertSame($c->value, $restored->value);
    }

    public function test_condition_group_and(): void
    {
        $group = ConditionGroup::and(
            new Condition('status', ComparisonOperator::Eq, 'active'),
            new Condition('amount', ComparisonOperator::Gt, 100),
        );

        self::assertSame(LogicOperator::And, $group->logic);
        self::assertCount(2, $group->conditions);
    }

    public function test_condition_group_or(): void
    {
        $group = ConditionGroup::or(
            new Condition('status', ComparisonOperator::Eq, 'active'),
            new Condition('status', ComparisonOperator::Eq, 'pending'),
        );

        self::assertSame(LogicOperator::Or, $group->logic);
    }

    public function test_condition_group_nested(): void
    {
        $group = ConditionGroup::and(
            new Condition('status', ComparisonOperator::Eq, 'active'),
            ConditionGroup::or(
                new Condition('amount', ComparisonOperator::Gt, 100),
                new Condition('amount', ComparisonOperator::Lt, 10),
            ),
        );

        self::assertCount(2, $group->conditions);
        self::assertInstanceOf(ConditionGroup::class, $group->conditions[1]);
    }

    public function test_condition_group_max_nesting(): void
    {
        // Build 6 levels deep — should fail at depth 6
        $inner = ConditionGroup::and(new Condition('a', ComparisonOperator::Eq, 1));
        for ($i = 0; $i < 5; $i++) {
            $inner = ConditionGroup::and($inner);
        }

        $this->expectException(QueryValidationException::class);
        $this->expectExceptionMessage('nesting depth');
        $inner->validateDepth();
    }

    public function test_condition_group_empty_throws(): void
    {
        $this->expectException(QueryValidationException::class);
        new ConditionGroup(LogicOperator::And, []);
    }

    public function test_condition_group_serialization_roundtrip(): void
    {
        $group = ConditionGroup::and(
            new Condition('status', ComparisonOperator::Eq, 'active'),
            ConditionGroup::or(
                new Condition('amount', ComparisonOperator::Gte, 50),
                new Condition('name', ComparisonOperator::Contains, 'test'),
            ),
        );

        $restored = ConditionGroup::fromArray($group->toArray());
        self::assertSame($group->logic, $restored->logic);
        self::assertCount(2, $restored->conditions);
        self::assertInstanceOf(ConditionGroup::class, $restored->conditions[1]);
    }

    public function test_order_by(): void
    {
        $o = new OrderBy('amount', SortDirection::Desc);
        self::assertSame(['field' => 'amount', 'direction' => 'desc'], $o->toArray());

        $restored = OrderBy::fromArray($o->toArray());
        self::assertSame($o->field, $restored->field);
        self::assertSame($o->direction, $restored->direction);
    }

    public function test_limit(): void
    {
        $l = new Limit(100, 50);
        self::assertSame(['limit' => 100, 'offset' => 50], $l->toArray());

        $noOffset = new Limit(25);
        self::assertSame(['limit' => 25], $noOffset->toArray());
    }

    public function test_limit_negative_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Limit(-1);
    }

    public function test_time_range_explicit(): void
    {
        $tr = new TimeRange('created_at', '2024-01-01 00:00:00', '2024-12-31 23:59:59');
        $range = $tr->resolveRange();

        self::assertSame('2024-01-01 00:00:00', $range['start']);
        self::assertSame('2024-12-31 23:59:59', $range['end']);
    }

    public function test_time_range_preset(): void
    {
        $now = new \DateTimeImmutable('2024-06-15 12:00:00', new \DateTimeZone('UTC'));
        $tr = new TimeRange('created_at', preset: TimePreset::Last7Days, grain: TimeBucket::Day);
        $range = $tr->resolveRange($now);

        self::assertSame('2024-06-09 00:00:00', $range['start']);
        self::assertSame('2024-06-15 23:59:59', $range['end']);
    }

    public function test_time_range_serialization(): void
    {
        $tr = new TimeRange('date', preset: TimePreset::ThisMonth, grain: TimeBucket::Week);
        $array = $tr->toArray();

        self::assertSame('date', $array['field']);
        self::assertSame('this_month', $array['preset']);
        self::assertSame('week', $array['grain']);
        self::assertArrayNotHasKey('start', $array);

        $restored = TimeRange::fromArray($array);
        self::assertSame($tr->field, $restored->field);
        self::assertSame($tr->preset, $restored->preset);
        self::assertSame($tr->grain, $restored->grain);
    }

    // --- Query tests ---

    public function test_browse_query(): void
    {
        $query = new Query(
            source: new Source('test_backend', 'items', ['id' => [1]]),
            type: QueryType::Browse,
            where: ConditionGroup::and(
                new Condition('status', ComparisonOperator::Eq, 'active'),
            ),
            orderBy: [new OrderBy('created_at', SortDirection::Desc)],
            limit: new Limit(25),
        );

        $query->validate();
        $array = $query->toArray();

        self::assertSame('browse', $array['type']);
        self::assertSame('test_backend', $array['source']['type']);
        self::assertArrayHasKey('where', $array);
        self::assertArrayHasKey('orderBy', $array);
        self::assertArrayHasKey('limit', $array);
    }

    public function test_aggregate_query(): void
    {
        $query = new Query(
            source: new Source('test_backend', 'items', ['id' => [1]]),
            type: QueryType::Aggregate,
            time: new TimeRange('created_at', preset: TimePreset::Last30Days, grain: TimeBucket::Day),
            dimensions: [new SelectField('status')],
            metrics: [new AggregateField(AggregateFunction::Count, alias: 'total')],
        );

        $query->validate();
        $array = $query->toArray();

        self::assertSame('aggregate', $array['type']);
        self::assertCount(1, $array['dimensions']);
        self::assertCount(1, $array['metrics']);
        self::assertArrayHasKey('time', $array);
    }

    public function test_aggregate_requires_metric_or_dimension(): void
    {
        $query = new Query(
            source: new Source('test'),
            type: QueryType::Aggregate,
        );

        $this->expectException(QueryValidationException::class);
        $query->validate();
    }

    public function test_having_only_for_aggregate(): void
    {
        $query = new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            having: ConditionGroup::and(
                new Condition('count', ComparisonOperator::Gt, 5),
            ),
        );

        $this->expectException(QueryValidationException::class);
        $query->validate();
    }

    public function test_query_from_array_roundtrip(): void
    {
        $query = new Query(
            source: new Source('test_backend', 'items', ['id' => [1]]),
            type: QueryType::Aggregate,
            dimensions: [new SelectField('status')],
            metrics: [new AggregateField(AggregateFunction::Sum, 'amount', 'total_amount')],
            where: ConditionGroup::and(
                new Condition('status', ComparisonOperator::Neq, 'trash'),
            ),
            orderBy: [new OrderBy('total_amount', SortDirection::Desc)],
            limit: new Limit(10),
            search: 'test',
        );

        $array = $query->toArray();
        $restored = Query::fromArray($array);

        self::assertSame($query->hash(), $restored->hash());
        self::assertSame($query->source->type, $restored->source->type);
        self::assertSame($query->type, $restored->type);
        self::assertCount(1, $restored->dimensions);
        self::assertCount(1, $restored->metrics);
        self::assertSame('test', $restored->search);
    }

    public function test_query_from_array_missing_source(): void
    {
        $this->expectException(QueryValidationException::class);
        Query::fromArray(['type' => 'browse']);
    }

    public function test_query_hash_deterministic(): void
    {
        $q1 = new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            limit: new Limit(10),
        );
        $q2 = new Query(
            source: new Source('test'),
            type: QueryType::Browse,
            limit: new Limit(10),
        );

        self::assertSame($q1->hash(), $q2->hash());
    }

    public function test_immutable_builder_with_where(): void
    {
        $query = new Query(source: new Source('test'), type: QueryType::Browse);
        $filtered = $query->withWhere(
            ConditionGroup::and(new Condition('a', ComparisonOperator::Eq, 1)),
        );

        self::assertNull($query->where);
        self::assertNotNull($filtered->where);
    }

    public function test_immutable_builder_with_limit(): void
    {
        $query = new Query(source: new Source('test'), type: QueryType::Browse);
        $limited = $query->withLimit(new Limit(50));

        self::assertNull($query->limit);
        self::assertSame(50, $limited->limit->limit);
    }

    public function test_immutable_builder_with_search(): void
    {
        $query = new Query(source: new Source('test'), type: QueryType::Browse);
        $searched = $query->withSearch('hello');

        self::assertNull($query->search);
        self::assertSame('hello', $searched->search);
    }

    // --- TimePreset resolution tests ---

    public function test_time_preset_today(): void
    {
        $now = new \DateTimeImmutable('2024-03-15 14:30:00', new \DateTimeZone('UTC'));
        [$start, $end] = TimePreset::Today->resolve($now);

        self::assertSame('2024-03-15', $start->format('Y-m-d'));
        self::assertSame('00:00:00', $start->format('H:i:s'));
        self::assertSame('23:59:59', $end->format('H:i:s'));
    }

    public function test_time_preset_last_month(): void
    {
        $now = new \DateTimeImmutable('2024-03-15 14:30:00', new \DateTimeZone('UTC'));
        [$start, $end] = TimePreset::LastMonth->resolve($now);

        self::assertSame('2024-02-01', $start->format('Y-m-d'));
        self::assertSame('2024-02-29', $end->format('Y-m-d')); // 2024 is leap year
    }

    public function test_time_preset_last_year(): void
    {
        $now = new \DateTimeImmutable('2024-06-15 12:00:00', new \DateTimeZone('UTC'));
        [$start, $end] = TimePreset::LastYear->resolve($now);

        self::assertSame('2023-01-01', $start->format('Y-m-d'));
        self::assertSame('2023-12-31', $end->format('Y-m-d'));
    }
}
