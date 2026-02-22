<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

use DataKit\DataViews\Query\ColumnType;

/**
 * Default hydrator that casts values per ColumnType.
 *
 * Strict mode: when a value can't be cast (e.g. "abc" to Integer), adds a warning
 * instead of silently coercing.
 *
 * @since $ver$
 */
final class DefaultResultHydrator implements ResultHydrator
{
    public function __construct(
        private readonly bool $strict = false,
    ) {
    }

    public function hydrate(array $schema, array $rows): array
    {
        $warnings = [];

        foreach ($rows as $rowIndex => &$row) {
            foreach ($schema as $column => $type) {
                if (!array_key_exists($column, $row)) {
                    continue;
                }

                $value = $row[$column];

                if ($value === null) {
                    continue;
                }

                $row[$column] = match ($type) {
                    ColumnType::Integer => $this->castInteger($value, $column, $rowIndex, $warnings),
                    ColumnType::Float => $this->castFloat($value, $column, $rowIndex, $warnings),
                    ColumnType::Boolean => $this->castBoolean($value),
                    ColumnType::Datetime => (string) $value,
                    ColumnType::String => (string) $value,
                };
            }
        }

        return ['rows' => $rows, 'warnings' => $warnings];
    }

    private function castInteger(mixed $value, string $column, int $rowIndex, array &$warnings): int|string
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if ($this->strict) {
            $warnings[] = sprintf('Row %d: cannot cast "%s" to integer for column "%s".', $rowIndex, $value, $column);

            return (string) $value;
        }

        return (int) $value;
    }

    private function castFloat(mixed $value, string $column, int $rowIndex, array &$warnings): float|string
    {
        if (is_float($value) || is_int($value)) {
            return (float) $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if ($this->strict) {
            $warnings[] = sprintf('Row %d: cannot cast "%s" to float for column "%s".', $rowIndex, $value, $column);

            return (string) $value;
        }

        return (float) $value;
    }

    private function castBoolean(mixed $value): bool
    {
        return (bool) $value;
    }
}
