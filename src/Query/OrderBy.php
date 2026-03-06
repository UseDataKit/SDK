<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * An ORDER BY clause.
 *
 * @since $ver$
 */
final class OrderBy
{
    public function __construct(
        public string $field,
        public SortDirection $direction = SortDirection::Asc,
    ) {
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'direction' => $this->direction->value,
        ];
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['field'])) {
            throw new \InvalidArgumentException("Missing required key 'field' for OrderBy.");
        }

        return new self(
            field: $data['field'],
            direction: SortDirection::from($data['direction'] ?? 'asc'),
        );
    }
}
