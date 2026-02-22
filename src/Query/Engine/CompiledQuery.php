<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

/**
 * A backend-specific compiled query representation.
 *
 * This is an opaque container — the engine passes it between compile() and execute().
 *
 * @since $ver$
 */
class CompiledQuery
{
    public function __construct(
        private readonly mixed $compiled,
        private readonly string $description = '',
    ) {
    }

    public function getCompiled(): mixed
    {
        return $this->compiled;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
