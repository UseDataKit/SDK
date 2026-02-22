<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

use DataKit\DataViews\Query\ColumnType;

/**
 * Query result — immutable, countable, iterable.
 *
 * @since $ver$
 */
final class Result implements \Countable, \IteratorAggregate
{
    /**
     * @param array<string, ColumnType> $schema     Column name => type mapping.
     * @param array<array<string, mixed>> $rows     Result rows.
     * @param int                       $totalCount Total matching count (before limit).
     * @param string[]                  $warnings   Non-fatal warnings.
     * @param bool                      $fromCache  Whether this result was served from cache.
     * @param int|null                  $limit      Applied limit.
     * @param int|null                  $offset     Applied offset.
     */
    public function __construct(
        private readonly array $schema,
        private readonly array $rows,
        private readonly int $totalCount,
        private readonly array $warnings = [],
        private readonly bool $fromCache = false,
        private readonly ?int $limit = null,
        private readonly ?int $offset = null,
    ) {
    }

    public function getSchema(): array
    {
        return $this->schema;
    }

    public function getRows(): array
    {
        return $this->rows;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function isFromCache(): bool
    {
        return $this->fromCache;
    }

    public function count(): int
    {
        return count($this->rows);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->rows);
    }

    /**
     * Token-economy summary for AI agents.
     *
     * Returns total count, schema, preview rows, and distributions
     * for low-cardinality columns.
     */
    public function summarize(int $previewRows = 5): array
    {
        $summary = [
            'total' => $this->totalCount,
            'returned' => count($this->rows),
            'schema' => array_map(
                static fn (ColumnType $type) => $type->value,
                $this->schema,
            ),
        ];

        if ($this->warnings !== []) {
            $summary['warnings'] = $this->warnings;
        }

        // Preview rows
        $summary['preview'] = array_slice($this->rows, 0, $previewRows);

        // Distributions for low-cardinality columns (cardinality <= 20)
        $distributions = [];
        foreach (array_keys($this->schema) as $column) {
            $values = array_column($this->rows, $column);
            $unique = array_count_values(
                array_map('strval', array_filter($values, static fn ($v) => $v !== null)),
            );

            if (count($unique) > 0 && count($unique) <= 20) {
                arsort($unique);
                $distributions[$column] = $unique;
            }
        }

        if ($distributions !== []) {
            $summary['distributions'] = $distributions;
        }

        return $summary;
    }

    public function toArray(): array
    {
        $data = [
            'schema' => array_map(
                static fn (ColumnType $type) => $type->value,
                $this->schema,
            ),
            'rows' => $this->rows,
            'total_count' => $this->totalCount,
        ];

        if ($this->warnings !== []) {
            $data['warnings'] = $this->warnings;
        }

        if ($this->fromCache) {
            $data['from_cache'] = true;
        }

        if ($this->limit !== null) {
            $data['limit'] = $this->limit;
        }

        if ($this->offset !== null) {
            $data['offset'] = $this->offset;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            schema: array_map(
                static fn (string $type) => ColumnType::from($type),
                $data['schema'] ?? [],
            ),
            rows: $data['rows'] ?? [],
            totalCount: $data['total_count'] ?? 0,
            warnings: $data['warnings'] ?? [],
            fromCache: $data['from_cache'] ?? false,
            limit: $data['limit'] ?? null,
            offset: $data['offset'] ?? null,
        );
    }

    /**
     * Creates a new Result with additional warnings.
     */
    public function withWarnings(string ...$warnings): self
    {
        return new self(
            $this->schema,
            $this->rows,
            $this->totalCount,
            array_merge($this->warnings, $warnings),
            $this->fromCache,
            $this->limit,
            $this->offset,
        );
    }

    /**
     * Creates a new Result marked as from cache.
     */
    public function asFromCache(): self
    {
        return new self(
            $this->schema,
            $this->rows,
            $this->totalCount,
            $this->warnings,
            true,
            $this->limit,
            $this->offset,
        );
    }
}
