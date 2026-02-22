<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

use DataKit\DataViews\Query\ColumnType;

/**
 * Interface for result value hydration/casting.
 *
 * @since $ver$
 */
interface ResultHydrator
{
    /**
     * Hydrates raw result rows according to the schema.
     *
     * @param array<string, ColumnType> $schema   Column name => type mapping.
     * @param array<array<string, mixed>> $rows   Raw result rows.
     *
     * @return array{rows: array<array<string, mixed>>, warnings: string[]}
     */
    public function hydrate(array $schema, array $rows): array;
}
