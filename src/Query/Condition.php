<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

use DataKit\DataViews\Query\Exception\QueryValidationException;

/**
 * A single filter condition.
 *
 * @since $ver$
 */
final class Condition
{
    /**
     * @param string             $field    The field to filter on.
     * @param ComparisonOperator $operator The comparison operator.
     * @param mixed              $value    The filter value.
     *
     * @throws QueryValidationException When value shape doesn't match operator requirements.
     */
    public function __construct(
        public string $field,
        public ComparisonOperator $operator,
        public mixed $value,
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->operator === ComparisonOperator::Between) {
            if (!is_array($this->value) || count($this->value) !== 2) {
                throw QueryValidationException::invalidValue(
                    $this->field,
                    'Between operator requires an array of exactly 2 elements [low, high].',
                );
            }

            if (!array_is_list($this->value)) {
                throw QueryValidationException::invalidValue(
                    $this->field,
                    'Between operator requires a list array [low, high], not an associative array.',
                );
            }

            return;
        }

        if ($this->operator === ComparisonOperator::In || $this->operator === ComparisonOperator::NotIn) {
            if (!is_array($this->value) || count($this->value) === 0) {
                throw QueryValidationException::invalidValue(
                    $this->field,
                    sprintf('%s operator requires a non-empty array.', $this->operator->value),
                );
            }

            return;
        }

        if ($this->operator->requiresNull()) {
            if ($this->value !== null) {
                throw QueryValidationException::invalidValue(
                    $this->field,
                    sprintf('%s operator requires null value.', $this->operator->value),
                );
            }

            return;
        }

        if (is_array($this->value)) {
            throw QueryValidationException::invalidValue(
                $this->field,
                sprintf('%s operator requires a scalar value, array given.', $this->operator->value),
            );
        }
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator->value,
            'value' => $this->value,
        ];
    }

    public static function fromArray(array $data): self
    {
        if (!isset($data['field'])) {
            throw new \InvalidArgumentException("Missing required key 'field' for Condition.");
        }

        if (!isset($data['operator'])) {
            throw new \InvalidArgumentException("Missing required key 'operator' for Condition.");
        }

        return new self(
            field: $data['field'],
            operator: ComparisonOperator::from($data['operator']),
            value: $data['value'] ?? null,
        );
    }
}
