<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

/**
 * Schema description of a backend's available fields and capabilities.
 *
 * Self-describing for AI agents: includes types, operators, enums, and descriptions.
 *
 * @since $ver$
 */
final class BackendSchema
{
    /**
     * @param string        $sourceType   Backend source type identifier.
     * @param string        $label        Human-readable label.
     * @param string        $description  AI-oriented description of the data.
     * @param Capability[]  $capabilities Supported capabilities.
     * @param FieldSchema[] $fields       Available field schemas.
     */
    public function __construct(
        public string $sourceType,
        public string $label,
        public string $description,
        public array $capabilities,
        public array $fields,
    ) {
    }

    /**
     * @return string[] Available field keys.
     */
    public function fieldNames(): array
    {
        return array_map(
            static fn (FieldSchema $f) => $f->key,
            $this->fields,
        );
    }

    public function hasField(string $key): bool
    {
        foreach ($this->fields as $field) {
            if ($field->key === $key) {
                return true;
            }
        }

        return false;
    }

    public function getField(string $key): ?FieldSchema
    {
        foreach ($this->fields as $field) {
            if ($field->key === $key) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Finds the closest matching field name via Levenshtein distance.
     *
     * @param string $badName The misspelled field name.
     *
     * @return string|null The closest match, or null if none within distance 3.
     */
    public function findClosestField(string $badName): ?string
    {
        $closest = null;
        $minDistance = 4; // Max distance threshold + 1

        foreach ($this->fields as $field) {
            $distance = levenshtein($badName, $field->key);

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closest = $field->key;
            }
        }

        return $closest;
    }

    public function toArray(): array
    {
        return [
            'source_type' => $this->sourceType,
            'label' => $this->label,
            'description' => $this->description,
            'capabilities' => array_map(
                static fn (Capability $c) => $c->value,
                $this->capabilities,
            ),
            'fields' => array_map(
                static fn (FieldSchema $f) => $f->toArray(),
                $this->fields,
            ),
        ];
    }
}
