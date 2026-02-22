<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

/**
 * Query cost estimate from a backend.
 *
 * @since $ver$
 */
final readonly class CostEstimate
{
    /**
     * @param float  $score       Normalized cost score (0.0–1.0+).
     * @param bool   $isExpensive Whether this query is considered expensive.
     * @param array  $factors     Cost factors (e.g. entry_count, join_count, unnest_factor).
     * @param array  $suggestions User-facing suggestions for reducing cost.
     */
    public function __construct(
        public float $score,
        public bool $isExpensive,
        public array $factors = [],
        public array $suggestions = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'is_expensive' => $this->isExpensive,
            'factors' => $this->factors,
            'suggestions' => $this->suggestions,
        ];
    }
}
