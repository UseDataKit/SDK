<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;

/**
 * Schema definition for a single field.
 *
 * @since $ver$
 */
final class FieldSchema
{
    /**
     * @param string               $key          Field key.
     * @param string               $label        Human-readable label.
     * @param ColumnType           $type         Column data type.
     * @param ComparisonOperator[] $operators    Supported comparison operators for this field.
     * @param array|null           $enumValues   For choice fields: value => label map.
     * @param string|null          $description  AI hint about field purpose.
     * @param bool                 $sortable     Whether the field supports ORDER BY.
     * @param bool                 $filterable   Whether the field supports WHERE conditions.
     * @param bool                 $aggregatable Whether the field can be used in aggregate functions.
     * @param string|null          $timezone     Timezone info: 'utc', 'local', or null (assumed UTC).
     */
    public function __construct(
        public string $key,
        public string $label,
        public ColumnType $type,
        public array $operators = [],
        public ?array $enumValues = null,
        public ?string $description = null,
        public bool $sortable = true,
        public bool $filterable = true,
        public bool $aggregatable = false,
        public ?string $timezone = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type->value,
            'operators' => array_map(
                static fn (ComparisonOperator $op) => $op->value,
                $this->operators,
            ),
            'sortable' => $this->sortable,
            'filterable' => $this->filterable,
            'aggregatable' => $this->aggregatable,
        ];

        if ($this->enumValues !== null) {
            $data['enum_values'] = $this->enumValues;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->timezone !== null) {
            $data['timezone'] = $this->timezone;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            label: $data['label'],
            type: ColumnType::from($data['type']),
            operators: array_map(
                static fn (string $op) => ComparisonOperator::from($op),
                $data['operators'] ?? [],
            ),
            enumValues: $data['enum_values'] ?? null,
            description: $data['description'] ?? null,
            sortable: $data['sortable'] ?? true,
            filterable: $data['filterable'] ?? true,
            aggregatable: $data['aggregatable'] ?? false,
            timezone: $data['timezone'] ?? null,
        );
    }
}
