<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * LIMIT/OFFSET for query pagination.
 *
 * @since $ver$
 */
final class Limit
{
    public function __construct(
        public int $limit,
        public int $offset = 0,
    ) {
        if ($limit < 0) {
            throw new \InvalidArgumentException('Limit must be non-negative.');
        }

        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset must be non-negative.');
        }
    }

    public function toArray(): array
    {
        $data = ['limit' => $this->limit];

        if ($this->offset > 0) {
            $data['offset'] = $this->offset;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            limit: (int) ($data['limit'] ?? 0),
            offset: (int) ($data['offset'] ?? 0),
        );
    }
}
