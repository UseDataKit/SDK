<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * A field selection (dimension) in a query.
 *
 * @since $ver$
 */
final class SelectField
{
    /**
     * @param string      $field The field key.
     * @param string|null $alias Optional alias for the output column.
     */
    public function __construct(
        public string $field,
        public ?string $alias = null,
    ) {
    }

    public function outputName(): string
    {
        return $this->alias ?? $this->field;
    }

    public function toArray(): array
    {
        $data = ['field' => $this->field];

        if ($this->alias !== null) {
            $data['alias'] = $this->alias;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            field: $data['field'],
            alias: $data['alias'] ?? null,
        );
    }
}
