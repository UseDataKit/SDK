<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Exception;

/**
 * Thrown when a query requires a capability the backend doesn't support.
 *
 * @since $ver$
 */
class UnsupportedCapabilityException extends QueryException
{
    public static function create(string $capability, array $supportedCapabilities = []): self
    {
        $e = new self(sprintf('Unsupported capability: %s', $capability));
        $e->context = [
            'capability' => $capability,
            'supported_capabilities' => $supportedCapabilities,
        ];

        return $e;
    }
}
