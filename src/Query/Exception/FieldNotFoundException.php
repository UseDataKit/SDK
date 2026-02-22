<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Exception;

/**
 * Thrown when a query references a field that doesn't exist in the schema.
 *
 * Includes Levenshtein-based suggestion for the closest matching field.
 *
 * @since $ver$
 */
class FieldNotFoundException extends QueryException
{
    public static function create(string $field, ?string $suggestion = null, array $availableFields = []): self
    {
        $message = sprintf('Field "%s" not found.', $field);

        if ($suggestion !== null) {
            $message .= sprintf(' Did you mean "%s"?', $suggestion);
        }

        $e = new self($message);
        $e->context = [
            'field' => $field,
            'suggestion' => $suggestion,
            'available_fields' => $availableFields,
        ];

        return $e;
    }
}
