<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\WPForms;

/**
 * Reads a WPForms form definition.
 *
 * Exists so the backend's field discovery can be exercised without a live
 * WPForms install; production always uses {@see WPFormsApiFormRepository}.
 *
 * @since $ver$
 */
interface WPFormsFormRepository
{
    /**
     * Return the decoded form definition, or null when the form is unreadable.
     *
     * The array is WPForms' own `content_only` shape: `fields` is a list of
     * field definitions each carrying `id`, `type` and `label`, and `settings`
     * holds per-form toggles such as `quiz_enable`.
     *
     * @param int $formId WPForms form ID.
     *
     * @return array<string, mixed>|null
     */
    public function getForm(int $formId): ?array;
}
