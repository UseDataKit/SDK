<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Exception;

/**
 * Thrown when two backends claim the same source type.
 *
 * @since $ver$
 */
final class BackendCollisionException extends QueryException
{
    /**
     * @param string $sourceType Source type both backends claim.
     * @param string $registered Class of the backend already holding the type.
     * @param string $incoming   Class of the backend being registered.
     */
    public static function create(string $sourceType, string $registered, string $incoming): self
    {
        $e = new self(sprintf(
            'Source type "%s" is already served by %s; %s cannot take it silently. '
            . 'Pass replace: true (or call replace()) to substitute it deliberately.',
            $sourceType,
            $registered,
            $incoming,
        ));

        $e->context = [
            'source_type' => $sourceType,
            'registered' => $registered,
            'incoming' => $incoming,
        ];

        return $e;
    }
}
