<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

use DataKit\DataViews\Query\Exception\BackendCollisionException;

/**
 * Registry of available query backends, keyed by source type.
 *
 * @since $ver$
 */
final class BackendRegistry
{
    /** @var array<string, QueryBackend> */
    private array $backends = [];

    /**
     * Register a backend for its source type.
     *
     * One source type resolves to exactly one backend, so a second claim on a
     * type is refused rather than applied: whichever backend lost would answer
     * no query and leave no trace of having been registered.
     *
     * @param QueryBackend $backend The backend to register.
     * @param bool         $replace Deliberately substitute an already-registered
     *                              backend for this source type. This is how a
     *                              consumer overrides a shipped backend (e.g. to
     *                              patch one ahead of a release); it is not a way
     *                              to layer two backends over one type.
     *
     * @throws BackendCollisionException If the source type is taken and $replace is false.
     */
    public function register(QueryBackend $backend, bool $replace = false): void
    {
        $sourceType = $backend->sourceType();
        $registered = $this->backends[$sourceType] ?? null;

        $contested = $registered !== null && $registered !== $backend && !$replace;

        if ($contested) {
            throw BackendCollisionException::create(
                $sourceType,
                $registered::class,
                $backend::class,
            );
        }

        $this->backends[$sourceType] = $backend;
    }

    /**
     * Register a backend, substituting any backend already serving its source type.
     *
     * @param QueryBackend $backend The backend to register.
     */
    public function replace(QueryBackend $backend): void
    {
        $this->register($backend, true);
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
