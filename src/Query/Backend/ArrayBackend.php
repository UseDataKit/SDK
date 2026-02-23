<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\CompiledQuery;
use DataKit\DataViews\Query\Engine\CostEstimate;
use DataKit\DataViews\Query\Engine\FieldSchema;
use DataKit\DataViews\Query\Engine\QueryBackend;
use DataKit\DataViews\Query\Engine\Result;
use DataKit\DataViews\Query\LogicOperator;
use DataKit\DataViews\Query\OrderBy;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\SortDirection;
use DataKit\DataViews\Query\TimeBucket;

/**
 * In-memory reference implementation supporting ALL capabilities.
 *
 * Used as a test oracle to verify correctness of SQL backends.
 *
 * @since $ver$
 */
final class ArrayBackend implements QueryBackend
{
    /**
     * @param string        $sourceType   Source type identifier.
     * @param array         $data         Row data: array<array<string, mixed>>.
     * @param FieldSchema[] $fieldSchemas Field schema definitions.
     */
    public function __construct(
        private readonly string $sourceType,
        private readonly array $data,
        private readonly array $fieldSchemas = [],
    ) {
    }

    public static function isAvailable(): bool
    {
        return true;
    }

    public function sourceType(): string
    {
        return $this->sourceType;
    }

    public function capabilities(): array
    {
        return Capability::cases();
    }

    public function supports(Capability $cap): bool
    {
        return true;
    }

    public function describe(array $scope): BackendSchema
    {
        $fields = $this->fieldSchemas;

        if ($fields === [] && isset($this->data[0])) {
            $fields = array_map(
                static fn (string $key) => new FieldSchema($key, ucfirst($key), ColumnType::String),
                array_keys($this->data[0]),
            );
        }

        return new BackendSchema(
            $this->sourceType,
            'Array Backend',
            'In-memory data source.',
            $this->capabilities(),
            $fields,
        );
    }

    public function schemaVersion(): int
    {
        return 1;
    }

    public function estimate(Query $query): ?CostEstimate
    {
        $score = count($this->data) / 10000;

        return new CostEstimate(
            min($score, 1.0),
            $score > 0.5,
            ['entry_count' => count($this->data)],
        );
    }

    public function compile(Query $query, BackendSchema $schema): CompiledQuery
    {
        return new CompiledQuery($query, 'ArrayBackend in-memory execution');
    }

    public function execute(CompiledQuery $compiled): Result
    {
        /** @var Query $query */
        $query = $compiled->getCompiled();
        $rows = $this->data;

        // 1. Time range filter
        if ($query->time !== null) {
            $range = $query->time->resolveRange();
            $field = $query->time->field;
            $rows = array_filter($rows, static function (array $row) use ($field, $range): bool {
                $value = $row[$field] ?? null;

                if ($value === null) {
                    return false;
                }

                if ($range['start'] !== null && $value < $range['start']) {
                    return false;
                }

                if ($range['end'] !== null && $value > $range['end']) {
                    return false;
                }

                return true;
            });
        }

        // 2. WHERE filter
        if ($query->where !== null) {
            $rows = array_filter($rows, fn (array $row): bool => $this->matchGroup($row, $query->where));
        }

        // 3. Search
        if ($query->search !== null) {
            $search = mb_strtolower($query->search);
            $rows = array_filter($rows, static function (array $row) use ($search): bool {
                foreach ($row as $value) {
                    if ($value !== null && str_contains(mb_strtolower((string) $value), $search)) {
                        return true;
                    }
                }

                return false;
            });
        }

        $rows = array_values($rows);

        if ($query->type === QueryType::Browse) {
            return $this->executeBrowse($query, $rows);
        }

        return $this->executeAggregate($query, $rows);
    }

    private function executeBrowse(Query $query, array $rows): Result
    {
        // Sort
        $rows = $this->sortRows($rows, $query->orderBy);

        // Total count (after filter, before limit)
        $totalCount = count($rows);

        // Limit/offset
        if ($query->limit !== null) {
            $rows = array_slice($rows, $query->limit->offset, $query->limit->limit);
        }

        // Build schema
        $schema = $this->buildResultSchema($rows);

        return new Result($schema, $rows, $totalCount);
    }

    private function executeAggregate(Query $query, array $rows): Result
    {
        // Group by dimensions (including time bucketing)
        $groups = $this->groupRows($rows, $query->dimensions, $query->time);

        // Compute metrics per group
        $resultRows = [];
        foreach ($groups as $groupKey => $groupRows) {
            $resultRow = json_decode($groupKey, true) ?: [];

            foreach ($query->metrics as $metric) {
                $resultRow[$metric->outputName()] = $this->computeAggregate($metric, $groupRows);
            }

            $resultRows[] = $resultRow;
        }

        // HAVING filter
        if ($query->having !== null) {
            $resultRows = array_values(
                array_filter($resultRows, fn (array $row): bool => $this->matchGroup($row, $query->having)),
            );
        }

        // Sort
        $resultRows = $this->sortRows($resultRows, $query->orderBy);

        $totalCount = count($resultRows);

        // Limit/offset
        if ($query->limit !== null) {
            $resultRows = array_slice($resultRows, $query->limit->offset, $query->limit->limit);
        }

        // Build schema
        $schema = [];
        foreach ($query->dimensions as $dim) {
            $schema[$dim->outputName()] = ColumnType::String;
        }
        if ($query->time?->grain !== null) {
            $schema[$query->time->field . '_bucket'] = ColumnType::String;
        }
        foreach ($query->metrics as $metric) {
            $schema[$metric->outputName()] = match ($metric->function) {
                AggregateFunction::Count, AggregateFunction::CountDistinct => ColumnType::Integer,
                AggregateFunction::Avg => ColumnType::Float,
                default => ColumnType::Float,
            };
        }

        return new Result($schema, $resultRows, $totalCount);
    }

    /**
     * Group rows by dimension fields and optional time bucket.
     *
     * @return array<string, array<array<string, mixed>>>
     */
    private function groupRows(array $rows, array $dimensions, ?\DataKit\DataViews\Query\TimeRange $time): array
    {
        if ($dimensions === [] && ($time === null || $time->grain === null)) {
            return [json_encode([]) => $rows];
        }

        $groups = [];

        foreach ($rows as $row) {
            $key = [];

            foreach ($dimensions as $dim) {
                $key[$dim->outputName()] = $row[$dim->field] ?? null;
            }

            if ($time?->grain !== null) {
                $bucketKey = $time->field . '_bucket';
                $dateValue = $row[$time->field] ?? null;
                $key[$bucketKey] = $dateValue !== null
                    ? $this->timeBucket($dateValue, $time->grain)
                    : null;
            }

            $groupKey = json_encode($key);
            $groups[$groupKey][] = $row;
        }

        return $groups;
    }

    private function timeBucket(string $dateValue, TimeBucket $grain): string
    {
        $dt = new \DateTimeImmutable($dateValue, new \DateTimeZone('UTC'));

        return match ($grain) {
            TimeBucket::Hour => $dt->format('Y-m-d H:00:00'),
            TimeBucket::Day => $dt->format('Y-m-d'),
            TimeBucket::Week => $dt->modify('monday this week')->format('Y-m-d'),
            TimeBucket::Month => $dt->format('Y-m-01'),
            TimeBucket::Quarter => $dt->format('Y') . '-Q' . ceil((int) $dt->format('n') / 3),
            TimeBucket::Year => $dt->format('Y-01-01'),
        };
    }

    private function computeAggregate(AggregateField $metric, array $rows): int|float|null
    {
        if ($metric->function === AggregateFunction::Count && $metric->field === null) {
            return count($rows);
        }

        $values = array_filter(
            array_map(
                static fn (array $row) => $row[$metric->field] ?? null,
                $rows,
            ),
            static fn ($v) => $v !== null,
        );

        if ($values === []) {
            return match ($metric->function) {
                AggregateFunction::Count, AggregateFunction::CountDistinct => 0,
                default => null,
            };
        }

        return match ($metric->function) {
            AggregateFunction::Count => count($values),
            AggregateFunction::CountDistinct => count(array_unique($values, SORT_REGULAR)),
            AggregateFunction::Sum => array_sum(array_map('floatval', $values)),
            AggregateFunction::Avg => array_sum(array_map('floatval', $values)) / count($values),
            AggregateFunction::Min => min(array_map('floatval', $values)),
            AggregateFunction::Max => max(array_map('floatval', $values)),
        };
    }

    /**
     * Sort rows by multiple OrderBy clauses.
     *
     * @param OrderBy[] $orderBy
     */
    private function sortRows(array $rows, array $orderBy): array
    {
        if ($orderBy === []) {
            return $rows;
        }

        usort($rows, static function (array $a, array $b) use ($orderBy): int {
            foreach ($orderBy as $order) {
                $va = $a[$order->field] ?? null;
                $vb = $b[$order->field] ?? null;

                // Nulls sort last
                if ($va === null && $vb === null) {
                    continue;
                }
                if ($va === null) {
                    return 1;
                }
                if ($vb === null) {
                    return -1;
                }

                $cmp = is_numeric($va) && is_numeric($vb)
                    ? ($va <=> $vb)
                    : strcmp((string) $va, (string) $vb);

                if ($cmp !== 0) {
                    return $order->direction === SortDirection::Desc ? -$cmp : $cmp;
                }
            }

            return 0;
        });

        return $rows;
    }

    private function matchGroup(array $row, ConditionGroup $group): bool
    {
        foreach ($group->conditions as $condition) {
            $match = $condition instanceof Condition
                ? $this->matchCondition($row, $condition)
                : $this->matchGroup($row, $condition);

            if ($group->logic === LogicOperator::Or && $match) {
                return true;
            }

            if ($group->logic === LogicOperator::And && !$match) {
                return false;
            }
        }

        return $group->logic === LogicOperator::And;
    }

    private function matchCondition(array $row, Condition $condition): bool
    {
        $value = $row[$condition->field] ?? null;

        return match ($condition->operator) {
            ComparisonOperator::Eq => $value !== null && $this->looseEqual($value, $condition->value),
            ComparisonOperator::Neq => $value !== null && !$this->looseEqual($value, $condition->value),
            ComparisonOperator::Gt => $value !== null && $this->compareNumeric($value, $condition->value) > 0,
            ComparisonOperator::Gte => $value !== null && $this->compareNumeric($value, $condition->value) >= 0,
            ComparisonOperator::Lt => $value !== null && $this->compareNumeric($value, $condition->value) < 0,
            ComparisonOperator::Lte => $value !== null && $this->compareNumeric($value, $condition->value) <= 0,
            ComparisonOperator::In => $value !== null && $this->inArray($value, $condition->value),
            ComparisonOperator::NotIn => $value !== null && !$this->inArray($value, $condition->value),
            ComparisonOperator::Between => $value !== null
                && $this->compareNumeric($value, $condition->value[0]) >= 0
                && $this->compareNumeric($value, $condition->value[1]) <= 0,
            ComparisonOperator::Contains => $value !== null
                && str_contains(mb_strtolower((string) $value), mb_strtolower((string) $condition->value)),
            ComparisonOperator::NotContains => $value !== null
                && !str_contains(mb_strtolower((string) $value), mb_strtolower((string) $condition->value)),
            ComparisonOperator::StartsWith => $value !== null
                && str_starts_with(mb_strtolower((string) $value), mb_strtolower((string) $condition->value)),
            ComparisonOperator::IsEmpty => $value === null || $value === '',
            ComparisonOperator::IsNotEmpty => $value !== null && $value !== '',
        };
    }

    /**
     * Loose equality: compare as numbers if both numeric, else as strings.
     */
    private function looseEqual(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }

        return (string) $a === (string) $b;
    }

    /**
     * Compare values as numbers if both numeric, else as strings.
     */
    private function compareNumeric(mixed $a, mixed $b): int
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a <=> (float) $b;
        }

        return strcmp((string) $a, (string) $b);
    }

    /**
     * Check if value is in array (with loose number comparison).
     */
    private function inArray(mixed $needle, array $haystack): bool
    {
        foreach ($haystack as $item) {
            if ($this->looseEqual($needle, $item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build a result schema from row data, inferring types.
     */
    private function buildResultSchema(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $schema = [];
        foreach (array_keys($rows[0]) as $key) {
            // Use field schemas if available
            foreach ($this->fieldSchemas as $fs) {
                if ($fs->key === $key) {
                    $schema[$key] = $fs->type;
                    continue 2;
                }
            }

            $schema[$key] = ColumnType::String;
        }

        return $schema;
    }
}
