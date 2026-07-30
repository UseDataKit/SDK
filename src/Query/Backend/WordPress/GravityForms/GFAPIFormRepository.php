<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\GravityForms;

/**
 * Reads form definitions through Gravity Forms' own API.
 *
 * @since $ver$
 */
final class GFAPIFormRepository implements GravityFormsFormRepository
{
    /** @var array<int, array<string, mixed>|null> */
    private array $cache = [];

    public function getForm(int $formId): ?array
    {
        if (array_key_exists($formId, $this->cache)) {
            return $this->cache[$formId];
        }

        $form = null;

        if (class_exists('\GFAPI')) {
            $result = \GFAPI::get_form($formId);
            $form = is_array($result) ? $result : null;
        }

        $this->cache[$formId] = $form;

        return $form;
    }
}
