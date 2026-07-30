<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\WPForms;

/**
 * Reads form definitions through WPForms' own form handler.
 *
 * @since $ver$
 */
final class WPFormsApiFormRepository implements WPFormsFormRepository
{
    /** @var array<int, array<string, mixed>|null> */
    private array $cache = [];

    public function getForm(int $formId): ?array
    {
        if (array_key_exists($formId, $this->cache)) {
            return $this->cache[$formId];
        }

        $form = null;

        if (function_exists('wpforms')) {
            $handler = wpforms()->obj('form');

            if (is_object($handler) && method_exists($handler, 'get')) {
                $result = $handler->get($formId, ['content_only' => true]);
                $form = is_array($result) ? $result : null;
            }
        }

        $this->cache[$formId] = $form;

        return $form;
    }
}
