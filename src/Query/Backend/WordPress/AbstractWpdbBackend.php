<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress;

use DataKit\DataViews\Query\AggregateField;
use DataKit\DataViews\Query\AggregateFunction;
use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\CompiledQuery;
use DataKit\DataViews\Query\Engine\CostEstimate;
use DataKit\DataViews\Query\Engine\QueryBackend;
use DataKit\DataViews\Query\Engine\Result;
use DataKit\DataViews\Query\Exception\QueryValidationException;
use DataKit\DataViews\Query\OrderBy;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;
use DataKit\DataViews\Query\SelectField;
use DataKit\DataViews\Query\SortDirection;

/**
 * Abstract base for WordPress SQL backends.
 *
 * Provides shared compilation logic (filters, aggregates, time bucketing, ordering).
 * Subclasses provide: base table, system columns, JOIN construction, scope filtering.
 *
 * @since $ver$
 */
abstract class AbstractWpdbBackend implements QueryBackend
{
    protected readonly SqlFilterCompiler $filterCompiler;
    protected readonly SqlTimeBucketCompiler $timeBucketCompiler;
    protected readonly SqlAggregateCompiler $aggregateCompiler;
    protected readonly WpdbExecutor $executor;

    protected int $joinCounter = 0;

    public function __construct()
    {
        $this->filterCompiler = new SqlFilterCompiler();
        $this->timeBucketCompiler = new SqlTimeBucketCompiler();
        $this->aggregateCompiler = new SqlAggregateCompiler();
        $this->executor = new WpdbExecutor();
    }

    public function supports(Capability $cap): bool
    {
        return in_array($cap, $this->capabilities(), true);
    }

    public function schemaVersion(): int
    {
        return 1;
    }

    public function estimate(Query $query): ?CostEstimate
    {
        $rowCount = $this->estimateRowCount($query);

        if ($rowCount === null) {
            return null;
        }

        $joinCount = count($query->dimensions) + count($query->metrics);
        $unnestFactor = count($query->unnest) > 0 ? 2 : 1;
        $cost = $rowCount * max(1, $joinCount) * $unnestFactor;
        $score = min(1.0, $cost / 1_000_000);

        $suggestions = [];
        if ($rowCount > 10000 && $query->time === null) {
            $suggestions[] = 'Add a time filter to reduce the data scanned.';
        }
        if ($joinCount > 5) {
            $suggestions[] = 'Reduce the number of dimensions to lower query complexity.';
        }

        return new CostEstimate($score, $score > 0.5, [
            'entry_count' => $rowCount,
            'join_count' => $joinCount,
            'unnest_factor' => $unnestFactor,
        ], $suggestions);
    }

    public function compile(Query $query, BackendSchema $schema): CompiledQuery
    {
        $this->joinCounter = 0;

        // Build column map: field_key => SQL expression
        $columnMap = $this->buildColumnMap($query, $schema);

        // SELECT
        $select = $this->compileSelect($query, $columnMap);

        // FROM
        $from = $this->getFrom($query);

        // JOINs
        $joins = $this->getJoins();

        // WHERE
        [$whereClause, $whereParams] = $this->compileWhere($query, $columnMap);

        // GROUP BY
        $groupBy = $this->compileGroupBy($query, $columnMap);

        // HAVING
        [$havingClause, $havingParams] = $this->compileHaving($query, $columnMap);

        // ORDER BY
        $orderBy = $this->compileOrderBy($query, $columnMap);

        // LIMIT
        $limit = $query->limit?->limit;
        $offset = $query->limit?->offset ?? 0;

        $compiled = new WpdbCompiledQuery(
            select: $select,
            from: $from,
            joins: $joins,
            where: $whereClause,
            groupBy: $groupBy,
            orderBy: $orderBy,
            having: $havingClause,
            limit: $limit,
            offset: $offset,
            params: array_merge($whereParams, $havingParams),
            columnMap: $columnMap,
        );

        // Augment with output schema derived from query structure + subclass types.
        $outputSchema = $this->buildOutputSchema($query, $compiled);

        return $outputSchema !== [] ? $compiled->withOutputSchema($outputSchema) : $compiled;
    }

    public function execute(CompiledQuery $compiled): Result
    {
        if (!$compiled instanceof WpdbCompiledQuery) {
            throw new \InvalidArgumentException('Expected WpdbCompiledQuery.');
        }

        // Use the output schema built during compile() if available,
        // falling back to the subclass schema for backward compatibility.
        $schema = $compiled->outputSchema !== [] ? $compiled->outputSchema : $this->getResultSchema($compiled);

        return $this->executor->execute($compiled, $schema);
    }

    // --- Abstract methods for subclasses ---

    /**
     * Build the column map for this query. Maps field keys to SQL expressions.
     * Subclasses construct JOINs during this phase via addJoin().
     *
     * @return array<string, string>
     */
    abstract protected function buildColumnMap(Query $query, BackendSchema $schema): array;

    /**
     * Get the FROM clause (e.g., "wp_gf_entry AS e").
     */
    abstract protected function getFrom(Query $query): string;

    /**
     * Get accumulated JOIN clauses.
     *
     * @return string[]
     */
    abstract protected function getJoins(): array;

    /**
     * Estimate the row count for cost calculation.
     */
    abstract protected function estimateRowCount(Query $query): ?int;

    /**
     * Get the result schema (column name => ColumnType) from the compiled query.
     *
     * @return array<string, ColumnType>
     */
    abstract protected function getResultSchema(WpdbCompiledQuery $compiled): array;

    // --- Shared compilation methods ---

    /**
     * Resolve a field key to its SQL expression, refusing unmapped keys.
     *
     * `buildColumnMap()` omits every key it cannot express as a column, so a
     * miss means the backend has no SQL for this field. Falling back to the
     * key itself would splice an unresolved name into the statement as a bare
     * expression. `SqlFilterCompiler` refuses the same miss, so a field the
     * backend cannot express fails the query wherever it appears.
     *
     * @param array<string, string> $columnMap
     *
     * @throws QueryValidationException When the field has no column mapping.
     */
    protected function resolveColumn(array $columnMap, string $field): string
    {
        $colExpr = $columnMap[$field] ?? null;

        if ($colExpr === null || $colExpr === '') {
            throw QueryValidationException::invalidValue(
                $field,
                'Field has no column mapping in this backend and cannot be compiled to SQL.',
            );
        }

        return $colExpr;
    }

    /**
     * Accept a meta key only if it is safe as a single-quoted SQL literal.
     *
     * EAV joins are assembled as raw strings, so a meta key reaches SQL as a
     * literal rather than a bound parameter: a quote in the key closes it. The
     * character set is the one Gravity Forms keys already had to satisfy —
     * field ids, add-on slugs, and ordinary underscore-prefixed meta names.
     *
     * Returns null for a key that cannot be expressed, which leaves the field
     * out of the column map; `resolveColumn()` then refuses it rather than
     * letting an unmapped field through.
     */
    protected function metaKeyLiteral(string $key): ?string
    {
        return preg_match('/^[A-Za-z0-9_.\-]+$/', $key) === 1 ? $key : null;
    }

    /**
     * Quote an output alias, escaping backticks so it cannot close its quoting.
     *
     * Schema field names come out of databases (meta keys, form field labels),
     * so "no identifier contains a backtick" is an assumption about data.
     */
    protected function quoteIdentifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    /**
     * @return string[] SELECT expressions.
     */
    protected function compileSelect(Query $query, array $columnMap): array
    {
        $select = [];

        // Dimensions
        foreach ($query->dimensions as $dim) {
            $colExpr = $this->resolveColumn($columnMap, $dim->field);
            $alias = $dim->outputName();
            $select[] = $colExpr . ' AS ' . $this->quoteIdentifier($alias);
        }

        // Time bucket
        if ($query->time?->grain !== null) {
            $colExpr = $this->resolveColumn($columnMap, $query->time->field);
            $bucketExpr = $this->timeBucketCompiler->compile($colExpr, $query->time->grain);
            $bucketAlias = $query->time->field . '_bucket';
            $select[] = $bucketExpr . ' AS ' . $this->quoteIdentifier($bucketAlias);
        }

        // Metrics
        foreach ($query->metrics as $metric) {
            $colExpr = $metric->field !== null ? $this->resolveColumn($columnMap, $metric->field) : null;
            $select[] = $this->aggregateCompiler->compile($metric, $colExpr);
        }

        // Browse mode — select all mapped fields if no explicit dimensions
        if ($query->type === QueryType::Browse && $select === []) {
            foreach ($columnMap as $key => $expr) {
                $select[] = $expr . ' AS ' . $this->quoteIdentifier((string) $key);
            }
        }

        return $select;
    }

    /**
     * @return array{0: string[], 1: array} WHERE clauses and params.
     */
    protected function compileWhere(Query $query, array $columnMap): array
    {
        $clauses = [];
        $params = [];

        // Scope filters (subclass may override)
        $scopeResult = $this->compileScopeWhere($query);
        if ($scopeResult['clause'] !== '') {
            $clauses[] = $scopeResult['clause'];
            $params = array_merge($params, $scopeResult['params']);
        }

        // Time range filter
        if ($query->time !== null) {
            $range = $query->time->resolveRange();
            $colExpr = $this->resolveColumn($columnMap, $query->time->field);

            if ($range['start'] !== null) {
                $clauses[] = "{$colExpr} >= %s";
                $params[] = $range['start'];
            }

            if ($range['end'] !== null) {
                $clauses[] = "{$colExpr} <= %s";
                $params[] = $range['end'];
            }
        }

        // User conditions
        if ($query->where !== null) {
            $filterResult = $this->filterCompiler->compile($query->where, $columnMap);

            if ($filterResult['clause'] !== '') {
                $clauses[] = $filterResult['clause'];
                $params = array_merge($params, $filterResult['params']);
            }
        }

        // Search
        if ($query->search !== null) {
            $searchResult = $this->compileSearch($query->search, $columnMap);

            if ($searchResult['clause'] !== '') {
                $clauses[] = $searchResult['clause'];
                $params = array_merge($params, $searchResult['params']);
            }
        }

        return [$clauses, $params];
    }

    /**
     * Compile scope-specific WHERE conditions.
     *
     * @return array{clause: string, params: array}
     */
    protected function compileScopeWhere(Query $query): array
    {
        return ['clause' => '', 'params' => []];
    }

    /**
     * Compile search to LIKE conditions on all string fields.
     *
     * @return array{clause: string, params: array}
     */
    protected function compileSearch(string $search, array $columnMap): array
    {
        $clauses = [];
        $params = [];
        $escaped = '%' . SqlFilterCompiler::escapeLike($search) . '%';

        foreach ($columnMap as $colExpr) {
            $clauses[] = "{$colExpr} LIKE %s";
            $params[] = $escaped;
        }

        if ($clauses === []) {
            return ['clause' => '', 'params' => []];
        }

        return [
            'clause' => '(' . implode(' OR ', $clauses) . ')',
            'params' => $params,
        ];
    }

    /**
     * @return string[] GROUP BY expressions.
     */
    protected function compileGroupBy(Query $query, array $columnMap): array
    {
        if ($query->type !== QueryType::Aggregate) {
            return [];
        }

        $groupBy = [];

        foreach ($query->dimensions as $dim) {
            $groupBy[] = $this->resolveColumn($columnMap, $dim->field);
        }

        if ($query->time?->grain !== null) {
            $colExpr = $this->resolveColumn($columnMap, $query->time->field);
            $groupBy[] = $this->timeBucketCompiler->compile($colExpr, $query->time->grain);
        }

        return $groupBy;
    }

    /**
     * @return array{0: string[], 1: array} HAVING clauses and params.
     */
    protected function compileHaving(Query $query, array $columnMap): array
    {
        if ($query->having === null) {
            return [[], []];
        }

        // HAVING resolves against output names, so the map has to carry every
        // name the SELECT produces. Built from metrics alone, a HAVING on a
        // dimension alias found nothing.
        $outputMap = [];

        foreach ($query->dimensions as $dim) {
            $outputMap[$dim->outputName()] = $this->resolveColumn($columnMap, $dim->field);
        }

        if ($query->time?->grain !== null) {
            $colExpr = $this->resolveColumn($columnMap, $query->time->field);
            $outputMap[$query->time->field . '_bucket'] = $this->timeBucketCompiler->compile($colExpr, $query->time->grain);
        }

        foreach ($query->metrics as $metric) {
            $colExpr = $metric->field !== null ? $this->resolveColumn($columnMap, $metric->field) : null;
            $outputMap[$metric->outputName()] = $this->aggregateCompiler->compile($metric, $colExpr);
            // Strip the alias for HAVING clause
            $outputMap[$metric->outputName()] = preg_replace('/ AS `.+`$/', '', $outputMap[$metric->outputName()]);
        }

        $filterResult = $this->filterCompiler->compile($query->having, $outputMap);

        if ($filterResult['clause'] === '') {
            return [[], []];
        }

        return [[$filterResult['clause']], $filterResult['params']];
    }

    /**
     * @return string[] ORDER BY expressions.
     */
    protected function compileOrderBy(Query $query, array $columnMap): array
    {
        $orderBy = [];

        // Build output alias map for aggregate queries
        $outputAliases = [];
        if ($query->type === QueryType::Aggregate) {
            foreach ($query->metrics as $metric) {
                $outputAliases[] = $metric->outputName();
            }
            foreach ($query->dimensions as $dim) {
                if ($dim->alias !== null) {
                    $outputAliases[] = $dim->alias;
                }
            }
            if ($query->time?->grain !== null) {
                $outputAliases[] = $query->time->field . '_bucket';
            }
        }

        foreach ($query->orderBy as $order) {
            $dir = $order->direction === SortDirection::Desc ? 'DESC' : 'ASC';

            // Use backtick-quoted alias for output names
            if (in_array($order->field, $outputAliases, true)) {
                $orderBy[] = $this->quoteIdentifier($order->field) . ' ' . $dir;
            } else {
                $colExpr = $this->resolveColumn($columnMap, $order->field);
                $orderBy[] = "{$colExpr} {$dir}";
            }
        }

        return $orderBy;
    }

    /**
     * Build the complete output schema from the Query structure.
     *
     * The subclass-provided {@see getResultSchema()} only knows about raw column
     * map entries. This method augments it with computed output columns: time bucket
     * aliases and aggregate metric aliases that appear in the SELECT but not in the
     * column map.
     *
     * @param Query             $query    The original query.
     * @param WpdbCompiledQuery $compiled The compiled query (for subclass type resolution).
     *
     * @return array<string, ColumnType>
     */
    protected function buildOutputSchema(Query $query, WpdbCompiledQuery $compiled): array
    {
        if ($query->type === QueryType::Browse) {
            // Browse mode — subclass schema covers all output columns.
            return [];
        }

        // Start with the subclass-derived types for raw column map fields.
        $baseTypes = $this->getResultSchema($compiled);
        $schema = [];

        // Dimensions — use the subclass type for the underlying field.
        foreach ($query->dimensions as $dim) {
            $schema[$dim->outputName()] = $baseTypes[$dim->field] ?? ColumnType::String;
        }

        // Time bucket alias (e.g. "created_at_bucket").
        if ($query->time?->grain !== null) {
            $schema[$query->time->field . '_bucket'] = ColumnType::Datetime;
        }

        // Metric aliases (e.g. "count_*", "sum_payment_amount").
        foreach ($query->metrics as $metric) {
            $schema[$metric->outputName()] = match ($metric->function) {
                AggregateFunction::Count, AggregateFunction::CountDistinct => ColumnType::Integer,
                default => ColumnType::Float,
            };
        }

        return $schema;
    }

    /**
     * Get next JOIN alias counter.
     */
    protected function nextJoinAlias(string $prefix = 'm'): string
    {
        $this->joinCounter++;

        return $prefix . $this->joinCounter;
    }
}
