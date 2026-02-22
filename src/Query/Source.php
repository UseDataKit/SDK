<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * Identifies the data source for a query.
 *
 * @since $ver$
 */
final readonly class Source
{
    /**
     * @param string $type   Source type identifier (e.g. "gravity_forms", "woocommerce").
     * @param string $entity Entity within the source (e.g. "entries", "orders", "products").
     * @param array  $scope  Source-specific scope (e.g. ["form_id" => [1, 2]]).
     */
    public function __construct(
        public string $type,
        public string $entity = '',
        public array $scope = [],
    ) {
    }

    public function toArray(): array
    {
        $data = ['type' => $this->type];

        if ($this->entity !== '') {
            $data['entity'] = $this->entity;
        }

        if ($this->scope !== []) {
            $data['scope'] = $this->scope;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? '',
            entity: $data['entity'] ?? '',
            scope: $data['scope'] ?? [],
        );
    }
}
