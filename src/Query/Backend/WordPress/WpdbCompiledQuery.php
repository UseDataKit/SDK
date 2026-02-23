<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress;

use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\Engine\CompiledQuery;

/**
 * Compiled SQL query for WordPress wpdb backends.
 *
 * Shared by all three SQL backends: GravityForms, WooCommerce, WordPressUsers.
 *
 * @since $ver$
 */
final class WpdbCompiledQuery extends CompiledQuery
{
    /**
     * @param string[] $select   SELECT expressions.
     * @param string   $from     FROM clause (table with alias).
     * @param string[] $joins    LEFT JOIN clauses.
     * @param string[] $where    WHERE conditions (ANDed).
     * @param string[] $groupBy  GROUP BY expressions.
     * @param string[] $orderBy  ORDER BY expressions.
     * @param string[] $having   HAVING conditions.
     * @param int|null $limit    LIMIT value.
     * @param int      $offset   OFFSET value.
     * @param array    $params   Bound parameters for wpdb->prepare().
     * @param array<string, string> $columnMap Field key => SQL expression mapping.
     * @param array<string, ColumnType> $outputSchema Output column name => type (all SELECT aliases).
     */
    public function __construct(
        public readonly array $select = [],
        public readonly string $from = '',
        public readonly array $joins = [],
        public readonly array $where = [],
        public readonly array $groupBy = [],
        public readonly array $orderBy = [],
        public readonly array $having = [],
        public readonly ?int $limit = null,
        public readonly int $offset = 0,
        public readonly array $params = [],
        public readonly array $columnMap = [],
        public readonly array $outputSchema = [],
    ) {
        parent::__construct($this, $this->toSql());
    }

    /**
     * Build the complete SQL statement.
     */
    public function toSql(): string
    {
        $sql = 'SELECT ' . ($this->select !== [] ? implode(', ', $this->select) : '1');
        $sql .= ' FROM ' . $this->from;

        if ($this->joins !== []) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        if ($this->where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        if ($this->groupBy !== []) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        if ($this->having !== []) {
            $sql .= ' HAVING ' . implode(' AND ', $this->having);
        }

        if ($this->orderBy !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;

            if ($this->offset > 0) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        return $sql;
    }

    /**
     * Get prepared SQL using wpdb->prepare() format.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Creates a new instance with the given output schema.
     *
     * @param array<string, ColumnType> $outputSchema
     */
    public function withOutputSchema(array $outputSchema): self
    {
        return new self(
            $this->select,
            $this->from,
            $this->joins,
            $this->where,
            $this->groupBy,
            $this->orderBy,
            $this->having,
            $this->limit,
            $this->offset,
            $this->params,
            $this->columnMap,
            $outputSchema,
        );
    }
}
