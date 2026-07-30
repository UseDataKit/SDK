<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\GravityForms;

/**
 * Reads a Gravity Forms form definition.
 *
 * Exists so field discovery can be exercised without a live Gravity Forms
 * install; production always uses {@see GFAPIFormRepository}.
 *
 * @since $ver$
 */
interface GravityFormsFormRepository
{
    /**
     * Return the form definition, or null when the form is unreadable.
     *
     * The array is Gravity Forms' own shape: `fields` is a list of field
     * definitions, each an object or array carrying `id`, `type`, `label` and,
     * for multi-input fields, `inputs`.
     *
     * @param int $formId Gravity Forms form ID.
     *
     * @return array<string, mixed>|null
     */
    public function getForm(int $formId): ?array;
}
