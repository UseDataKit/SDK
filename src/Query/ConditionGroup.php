<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

use DataKit\DataViews\Query\Exception\QueryValidationException;

/**
 * A group of conditions combined with AND/OR logic, supporting nesting.
 *
 * @since $ver$
 */
final readonly class ConditionGroup
{
    private const MAX_NESTING_DEPTH = 5;

    /**
     * @param LogicOperator                  $logic      The logical operator combining conditions.
     * @param array<Condition|ConditionGroup> $conditions The conditions or nested groups.
     */
    public function __construct(
        public LogicOperator $logic,
        public array $conditions,
    ) {
        if (count($conditions) === 0) {
            throw QueryValidationException::emptyConditionGroup();
        }

        foreach ($conditions as $condition) {
            if (!$condition instanceof Condition && !$condition instanceof self) {
                throw new \InvalidArgumentException(
                    'ConditionGroup conditions must be Condition or ConditionGroup instances.'
                );
            }
        }
    }

    /**
     * Convenience factory for AND groups.
     */
    public static function and(Condition|self ...$conditions): self
    {
        return new self(LogicOperator::And, array_values($conditions));
    }

    /**
     * Convenience factory for OR groups.
     */
    public static function or(Condition|self ...$conditions): self
    {
        return new self(LogicOperator::Or, array_values($conditions));
    }

    /**
     * Validates nesting depth does not exceed the maximum.
     *
     * @throws QueryValidationException When nesting depth exceeds MAX_NESTING_DEPTH.
     */
    public function validateDepth(int $currentDepth = 1): void
    {
        if ($currentDepth > self::MAX_NESTING_DEPTH) {
            throw QueryValidationException::nestingDepthExceeded(self::MAX_NESTING_DEPTH);
        }

        foreach ($this->conditions as $condition) {
            if ($condition instanceof self) {
                $condition->validateDepth($currentDepth + 1);
            }
        }
    }

    public function toArray(): array
    {
        return [
            'logic' => $this->logic->value,
            'conditions' => array_map(
                static fn (Condition|self $c) => $c->toArray(),
                $this->conditions,
            ),
        ];
    }

    public static function fromArray(array $data): self
    {
        $logic = LogicOperator::from($data['logic'] ?? 'and');
        $conditions = [];

        foreach ($data['conditions'] ?? [] as $item) {
            if (isset($item['logic'])) {
                $conditions[] = self::fromArray($item);
            } else {
                $conditions[] = Condition::fromArray($item);
            }
        }

        $group = new self($logic, $conditions);
        $group->validateDepth();

        return $group;
    }
}
