<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

/**
 * Registry of available query backends, keyed by source type.
 *
 * @since $ver$
 */
final class BackendRegistry
{
    /** @var array<string, QueryBackend> */
    private array $backends = [];

    public function register(QueryBackend $backend): void
    {
        $this->backends[$backend->sourceType()] = $backend;
    }

    public function get(string $sourceType): ?QueryBackend
    {
        return $this->backends[$sourceType] ?? null;
    }

    public function has(string $sourceType): bool
    {
        return isset($this->backends[$sourceType]);
    }

    /**
     * @return string[] Registered source types.
     */
    public function sourceTypes(): array
    {
        return array_keys($this->backends);
    }
}
