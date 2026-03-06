<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

use DataKit\DataViews\Query\Condition;
use DataKit\DataViews\Query\ConditionGroup;
use DataKit\DataViews\Query\Exception\CostThresholdExceededException;
use DataKit\DataViews\Query\Exception\FieldNotFoundException;
use DataKit\DataViews\Query\Exception\QueryExecutionException;
use DataKit\DataViews\Query\Exception\UnsupportedCapabilityException;
use DataKit\DataViews\Query\Limit;
use DataKit\DataViews\Query\LogicOperator;
use DataKit\DataViews\Query\Query;
use DataKit\DataViews\Query\QueryType;

/**
 * Query execution engine — the orchestration pipeline.
 *
 * Pipeline: resolve backend -> validate -> cache check -> estimate ->
 *   lifecycle.beforeCompile -> compile -> lifecycle.afterCompile ->
 *   lifecycle.beforeExecute -> execute -> lifecycle.afterExecute ->
 *   hydrate -> cache store.
 *
 * @since $ver$
 */
final class QueryEngine
{
    private readonly QueryLifecycle $lifecycle;

    public function __construct(
        private readonly BackendRegistry $backends,
        private readonly ?CacheProvider $cache = null,
        private readonly int $cacheTtl = 60,
        private readonly ?ResultHydrator $hydrator = null,
        private readonly bool $enforceCost = false,
        private readonly float $costThreshold = 0.8,
        private readonly int $maxLimit = 5000,
        ?QueryLifecycle $lifecycle = null,
    ) {
        $this->lifecycle = $lifecycle ?? new NullLifecycle();
    }

    /**
     * Execute a query through the full pipeline.
     *
     * @throws FieldNotFoundException
     * @throws UnsupportedCapabilityException
     * @throws CostThresholdExceededException
     * @throws QueryExecutionException
     */
    public function execute(Query $query): Result
    {
        // 1. Resolve backend
        $backend = $this->backends->get($query->source->type);

        if ($backend === null) {
            throw QueryExecutionException::create(
                $query->hash(),
                sprintf('No backend registered for source type "%s". Available: %s',
                    $query->source->type,
                    implode(', ', $this->backends->sourceTypes()) ?: 'none',
                ),
            );
        }

        // 2. Validate query structure
        $query->validate();

        // 3. Get schema and validate fields
        $schema = $backend->describe($query->source->scope);
        $this->validateFields($query, $schema);
        $this->validateCapabilities($query, $backend);

        // 4. Clamp limit
        $warnings = [];
        $query = $this->clampLimit($query, $warnings);

        // 5. Cache check
        $cacheKey = $this->cacheKey($backend, $query);

        if ($this->cache !== null && $cacheKey !== null) {
            $cached = $this->cache->get($cacheKey);

            if ($cached !== null) {
                return Result::fromArray($cached)->asFromCache();
            }
        }

        // 6. Cost estimation
        if ($this->enforceCost) {
            $estimate = $backend->estimate($query);

            if ($estimate !== null && $estimate->score > $this->costThreshold) {
                throw CostThresholdExceededException::create(
                    $estimate->toArray(),
                    $estimate->suggestions,
                );
            }
        }

        // 7. Lifecycle: beforeCompile
        $query = $this->lifecycle->beforeCompile($query);

        // 8. Compile
        $compiled = $backend->compile($query, $schema);

        // 9. Lifecycle: afterCompile
        $compiled = $this->lifecycle->afterCompile($compiled);

        // 10. Lifecycle: beforeExecute
        $compiled = $this->lifecycle->beforeExecute($compiled);

        // 11. Execute
        try {
            $result = $backend->execute($compiled);
        } catch (\Throwable $e) {
            $this->lifecycle->onError($e, $query);

            if ($e instanceof \DataKit\DataViews\Query\Exception\QueryException) {
                throw $e;
            }

            throw QueryExecutionException::create($query->hash(), $e->getMessage(), $e);
        }

        // 12. Lifecycle: afterExecute
        $result = $this->lifecycle->afterExecute($result);

        // 13. Hydrate
        if ($this->hydrator !== null) {
            $hydrated = $this->hydrator->hydrate($result->getSchema(), $result->getRows());
            $result = new Result(
                $result->getSchema(),
                $hydrated['rows'],
                $result->getTotalCount(),
                array_merge($result->getWarnings(), $hydrated['warnings']),
                false,
                $query->limit?->limit,
                $query->limit?->offset,
            );
        }

        // 14. Add engine warnings
        if ($warnings !== []) {
            $result = $result->withWarnings(...$warnings);
        }

        // 15. Cache store
        if ($this->cache !== null && $cacheKey !== null) {
            $this->cache->set($cacheKey, $result->toArray(), $this->cacheTtl);
        }

        return $result;
    }

    /**
     * Describe the schema for a source type and scope.
     */
    public function describe(string $sourceType, array $scope = []): BackendSchema
    {
        $backend = $this->backends->get($sourceType);

        if ($backend === null) {
            throw QueryExecutionException::create(
                '',
                sprintf('No backend registered for source type "%s".', $sourceType),
            );
        }

        return $backend->describe($scope);
    }

    /**
     * Validate that all referenced fields exist in the schema.
     */
    private function validateFields(Query $query, BackendSchema $schema): void
    {
        $fieldRefs = $this->collectFieldReferences($query);

        foreach ($fieldRefs as $fieldRef) {
            if (!$schema->hasField($fieldRef)) {
                $suggestion = $schema->findClosestField($fieldRef);

                throw FieldNotFoundException::create(
                    $fieldRef,
                    $suggestion,
                    $schema->fieldNames(),
                );
            }
        }
    }

    /**
     * Validate that the backend supports all required capabilities.
     */
    private function validateCapabilities(Query $query, QueryBackend $backend): void
    {
        $required = $this->collectRequiredCapabilities($query);

        foreach ($required as $capability) {
            if (!$backend->supports($capability)) {
                throw UnsupportedCapabilityException::create(
                    $capability->value,
                    array_map(
                        static fn (Capability $c) => $c->value,
                        $backend->capabilities(),
                    ),
                );
            }
        }
    }

    /**
     * Collect all source field names referenced by the query.
     *
     * Output aliases (metric aliases, dimension aliases, bucket columns)
     * are excluded since they don't exist in the source schema.
     *
     * @return string[]
     */
    private function collectFieldReferences(Query $query): array
    {
        $fields = [];

        // Build set of output aliases that are NOT source fields
        $outputAliases = [];
        foreach ($query->dimensions as $dim) {
            $fields[] = $dim->field;
            if ($dim->alias !== null) {
                $outputAliases[] = $dim->alias;
            }
        }

        foreach ($query->metrics as $metric) {
            if ($metric->field !== null) {
                $fields[] = $metric->field;
            }
            $outputAliases[] = $metric->outputName();
        }

        // Time bucket column is an output alias
        if ($query->time?->grain !== null) {
            $outputAliases[] = $query->time->field . '_bucket';
        }

        if ($query->where !== null) {
            $this->collectConditionFields($query->where, $fields);
        }

        // HAVING references output aliases, not source fields — skip validation
        // (having conditions reference aggregated column names)

        // ORDER BY can reference either source fields or output aliases
        foreach ($query->orderBy as $order) {
            if (!in_array($order->field, $outputAliases, true)) {
                $fields[] = $order->field;
            }
        }

        if ($query->time !== null) {
            $fields[] = $query->time->field;
        }

        foreach ($query->unnest as $unnestField) {
            $fields[] = $unnestField;
        }

        return array_unique($fields);
    }

    /**
     * Recursively collect field names from a condition group.
     */
    private function collectConditionFields(ConditionGroup $group, array &$fields): void
    {
        foreach ($group->conditions as $condition) {
            if ($condition instanceof Condition) {
                $fields[] = $condition->field;
            } elseif ($condition instanceof ConditionGroup) {
                $this->collectConditionFields($condition, $fields);
            }
        }
    }

    /**
     * Collect all capabilities required by the query.
     *
     * @return Capability[]
     */
    private function collectRequiredCapabilities(Query $query): array
    {
        $caps = [];

        // Conditions
        if ($query->where !== null) {
            $this->collectConditionCapabilities($query->where, $caps);
        }

        // Aggregate
        if ($query->type === QueryType::Aggregate) {
            if ($query->dimensions !== []) {
                $caps[] = Capability::GroupBy;
            }

            foreach ($query->metrics as $metric) {
                $caps[] = Capability::fromAggregateFunction($metric->function);
            }

            if ($query->having !== null) {
                $caps[] = Capability::Having;
                $this->collectConditionCapabilities($query->having, $caps);
            }
        }

        // Time bucketing
        if ($query->time?->grain !== null) {
            $caps[] = Capability::TimeBucket;
        }

        // Search
        if ($query->search !== null) {
            $caps[] = Capability::Search;
        }

        // Order by
        if ($query->orderBy !== []) {
            $caps[] = Capability::OrderBy;
        }

        // Limit
        if ($query->limit !== null) {
            $caps[] = Capability::LimitOffset;
        }

        // Unnest
        if ($query->unnest !== []) {
            $caps[] = Capability::Unnest;
        }

        return array_values(array_unique($caps, SORT_REGULAR));
    }

    /**
     * Recursively collect filter capabilities from conditions.
     */
    private function collectConditionCapabilities(ConditionGroup $group, array &$caps): void
    {
        if ($group->logic === LogicOperator::Or) {
            $caps[] = Capability::OrConditions;
        }

        foreach ($group->conditions as $condition) {
            if ($condition instanceof Condition) {
                $caps[] = Capability::fromComparisonOperator($condition->operator);
            } elseif ($condition instanceof ConditionGroup) {
                $this->collectConditionCapabilities($condition, $caps);
            }
        }
    }

    /**
     * Clamp query limit to maxLimit.
     */
    private function clampLimit(Query $query, array &$warnings): Query
    {
        if ($query->limit === null) {
            $warnings[] = sprintf('No limit specified; clamped to %d.', $this->maxLimit);

            return $query->withLimit(new Limit($this->maxLimit));
        }

        if ($query->limit->limit > $this->maxLimit) {
            $warnings[] = sprintf(
                'Limit %d exceeds maximum %d; clamped.',
                $query->limit->limit,
                $this->maxLimit,
            );

            return $query->withLimit(new Limit($this->maxLimit, $query->limit->offset));
        }

        return $query;
    }

    /**
     * Invalidate the cached result for a specific query.
     *
     * Resolves the backend, computes the cache key using the same logic as
     * execute(), and deletes the entry. Silently returns if no cache is
     * configured or the backend is unknown.
     */
    public function invalidate(Query $query): void
    {
        if ($this->cache === null) {
            return;
        }

        $backend = $this->backends->get($query->source->type);

        if ($backend === null) {
            return;
        }

        $cacheKey = $this->cacheKey($backend, $query);

        if ($cacheKey !== null) {
            $this->cache->delete($cacheKey);
        }
    }

    /**
     * Build a versioned cache key.
     *
     * Embeds the source type and scope identifiers (e.g. form IDs) so that
     * tag-based invalidation via CacheProvider::deleteByTag() can match
     * entries by substring.
     */
    private function cacheKey(QueryBackend $backend, Query $query): ?string
    {
        $tags = $query->source->type;

        // Append form-specific tags for Gravity Forms scope.
        $formIds = $query->source->scope['form_ids'] ?? $query->source->scope['form_id'] ?? [];

        if (!is_array($formIds)) {
            $formIds = [$formIds];
        }

        foreach ($formIds as $formId) {
            $tags .= '_form_' . (int) $formId;
        }

        return sprintf('%s_%d_%s', $tags, $backend->schemaVersion(), $query->hash());
    }
}
