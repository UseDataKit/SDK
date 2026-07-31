<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * An aggregate metric in a query.
 *
 * @since $ver$
 */
final class AggregateField
{
    /**
     * @param AggregateFunction $function The aggregate function.
     * @param string|null       $field    The field to aggregate (null for COUNT(*)).
     * @param string|null       $alias    Optional alias for the output column.
     */
    public function __construct(
        public AggregateFunction $function,
        public ?string $field = null,
        public ?string $alias = null,
    ) {
        if ($alias !== null) {
            OutputAlias::assertValid($alias, 'Metric');
        }
    }

    public function outputName(): string
    {
        if ($this->alias !== null) {
            return $this->alias;
        }

        $fieldPart = $this->field ?? '*';

        return $this->function->value . '_' . $fieldPart;
    }

    public function toArray(): array
    {
        $data = ['function' => $this->function->value];

        if ($this->field !== null) {
            $data['field'] = $this->field;
        }

        if ($this->alias !== null) {
            $data['alias'] = $this->alias;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            function: AggregateFunction::from($data['function']),
            field: $data['field'] ?? null,
            alias: $data['alias'] ?? null,
        );
    }
}
