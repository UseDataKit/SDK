<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

use DataKit\DataViews\Query\Exception\QueryValidationException;

/**
 * Immutable query value object — the core of the unified query layer.
 *
 * Serialization via toArray()/fromArray() IS the API contract for AI/MCP.
 *
 * @since $ver$
 */
final readonly class Query
{
    /**
     * @param Source               $source     The data source.
     * @param QueryType            $type       Browse or Aggregate.
     * @param TimeRange|null       $time       Optional time range filter.
     * @param SelectField[]        $dimensions Select/group-by fields.
     * @param AggregateField[]     $metrics    Aggregate metrics.
     * @param ConditionGroup|null  $where      WHERE conditions.
     * @param ConditionGroup|null  $having     HAVING conditions (aggregate only).
     * @param OrderBy[]            $orderBy    ORDER BY clauses.
     * @param Limit|null           $limit      LIMIT/OFFSET.
     * @param string[]             $unnest     Field keys to unnest (explode arrays into rows).
     * @param string|null          $search     Full-text search term.
     */
    public function __construct(
        public Source $source,
        public QueryType $type = QueryType::Aggregate,
        public ?TimeRange $time = null,
        public array $dimensions = [],
        public array $metrics = [],
        public ?ConditionGroup $where = null,
        public ?ConditionGroup $having = null,
        public array $orderBy = [],
        public ?Limit $limit = null,
        public array $unnest = [],
        public ?string $search = null,
    ) {
    }

    /**
     * Validates structural constraints.
     *
     * @throws QueryValidationException On invalid structure.
     */
    public function validate(): void
    {
        if ($this->type === QueryType::Aggregate
            && count($this->metrics) === 0
            && count($this->dimensions) === 0
        ) {
            throw QueryValidationException::aggregateRequiresMetricOrDimension();
        }

        if ($this->having !== null && $this->type !== QueryType::Aggregate) {
            throw QueryValidationException::havingOnlyForAggregate();
        }

        $this->where?->validateDepth();
        $this->having?->validateDepth();
    }

    /**
     * Deterministic array serialization.
     */
    public function toArray(): array
    {
        $data = [
            'source' => $this->source->toArray(),
            'type' => $this->type->value,
        ];

        if ($this->time !== null) {
            $data['time'] = $this->time->toArray();
        }

        if ($this->dimensions !== []) {
            $data['dimensions'] = array_map(
                static fn (SelectField $f) => $f->toArray(),
                $this->dimensions,
            );
        }

        if ($this->metrics !== []) {
            $data['metrics'] = array_map(
                static fn (AggregateField $f) => $f->toArray(),
                $this->metrics,
            );
        }

        if ($this->where !== null) {
            $data['where'] = $this->where->toArray();
        }

        if ($this->having !== null) {
            $data['having'] = $this->having->toArray();
        }

        if ($this->orderBy !== []) {
            $data['orderBy'] = array_map(
                static fn (OrderBy $o) => $o->toArray(),
                $this->orderBy,
            );
        }

        if ($this->limit !== null) {
            $data['limit'] = $this->limit->toArray();
        }

        if ($this->unnest !== []) {
            $data['unnest'] = $this->unnest;
        }

        if ($this->search !== null) {
            $data['search'] = $this->search;
        }

        ksort($data);

        return $data;
    }

    /**
     * Deserialize from array. Entry point for AI/MCP consumers.
     *
     * @throws QueryValidationException On invalid structure.
     * @throws \ValueError On invalid enum values.
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['source'])) {
            throw QueryValidationException::missingRequiredField('source');
        }

        $query = new self(
            source: Source::fromArray($data['source']),
            type: isset($data['type']) ? QueryType::from($data['type']) : QueryType::Aggregate,
            time: isset($data['time']) ? TimeRange::fromArray($data['time']) : null,
            dimensions: array_map(
                static fn (array $d) => SelectField::fromArray($d),
                $data['dimensions'] ?? [],
            ),
            metrics: array_map(
                static fn (array $m) => AggregateField::fromArray($m),
                $data['metrics'] ?? [],
            ),
            where: isset($data['where']) ? ConditionGroup::fromArray($data['where']) : null,
            having: isset($data['having']) ? ConditionGroup::fromArray($data['having']) : null,
            orderBy: array_map(
                static fn (array $o) => OrderBy::fromArray($o),
                $data['orderBy'] ?? [],
            ),
            limit: isset($data['limit']) ? Limit::fromArray($data['limit']) : null,
            unnest: $data['unnest'] ?? [],
            search: $data['search'] ?? null,
        );

        $query->validate();

        return $query;
    }

    /**
     * Deterministic hash for cache keying.
     */
    public function hash(): string
    {
        return md5(json_encode($this->toArray(), JSON_THROW_ON_ERROR));
    }

    // --- Immutable builder methods ---

    public function withWhere(ConditionGroup $where): self
    {
        return new self(
            $this->source, $this->type, $this->time,
            $this->dimensions, $this->metrics,
            $where, $this->having,
            $this->orderBy, $this->limit, $this->unnest, $this->search,
        );
    }

    public function withHaving(ConditionGroup $having): self
    {
        return new self(
            $this->source, $this->type, $this->time,
            $this->dimensions, $this->metrics,
            $this->where, $having,
            $this->orderBy, $this->limit, $this->unnest, $this->search,
        );
    }

    public function withLimit(Limit $limit): self
    {
        return new self(
            $this->source, $this->type, $this->time,
            $this->dimensions, $this->metrics,
            $this->where, $this->having,
            $this->orderBy, $limit, $this->unnest, $this->search,
        );
    }

    public function withOrderBy(OrderBy ...$orderBy): self
    {
        return new self(
            $this->source, $this->type, $this->time,
            $this->dimensions, $this->metrics,
            $this->where, $this->having,
            array_values($orderBy), $this->limit, $this->unnest, $this->search,
        );
    }

    public function withSearch(?string $search): self
    {
        return new self(
            $this->source, $this->type, $this->time,
            $this->dimensions, $this->metrics,
            $this->where, $this->having,
            $this->orderBy, $this->limit, $this->unnest, $search,
        );
    }

    public function withTime(?TimeRange $time): self
    {
        return new self(
            $this->source, $this->type, $time,
            $this->dimensions, $this->metrics,
            $this->where, $this->having,
            $this->orderBy, $this->limit, $this->unnest, $this->search,
        );
    }

    public function withType(QueryType $type): self
    {
        return new self(
            $this->source, $type, $this->time,
            $this->dimensions, $this->metrics,
            $this->where, $this->having,
            $this->orderBy, $this->limit, $this->unnest, $this->search,
        );
    }
}
