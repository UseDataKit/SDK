<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\FluentForms;

/**
 * Reads form definitions through Fluent Forms' own form parser.
 *
 * `FormFieldsParser::getInputs()` flattens the form definition to
 * `name => input` and expands container fields (name, address) into
 * additional `parent[child]` keys, keeping only the sub-inputs enabled in
 * the form editor. That bracket convention is parsed back into the
 * normalized `children` map here so the backend never sees it.
 *
 * @since $ver$
 */
final class FluentFormsApiFormRepository implements FluentFormsFormRepository
{
    /** @var array<int, array<int, array{name: string, element: string, label: string, children: array<string, string>}>> */
    private array $cache = [];

    public function getFields(int $formId): array
    {
        if (array_key_exists($formId, $this->cache)) {
            return $this->cache[$formId];
        }

        $fields = [];

        if (class_exists(\FluentForm\App\Modules\Form\FormFieldsParser::class)) {
            $inputs = \FluentForm\App\Modules\Form\FormFieldsParser::getInputs(
                $formId,
                ['admin_label', 'element'],
            );

            $fields = is_array($inputs) ? $this->normalize($inputs) : [];
        }

        $this->cache[$formId] = $fields;

        return $fields;
    }

    /**
     * Fold the parser's flat `name => input` map into the normalized list.
     *
     * @param array<string, mixed> $inputs
     *
     * @return array<int, array{name: string, element: string, label: string, children: array<string, string>}>
     */
    private function normalize(array $inputs): array
    {
        $byName = [];

        foreach ($inputs as $key => $input) {
            if (!is_array($input)) {
                continue;
            }

            $key = (string) $key;

            // Repeater sub-inputs are keyed `parent[0].*`; the parent key
            // itself carries the queryable identity.
            if (str_contains($key, '*')) {
                continue;
            }

            $label = isset($input['admin_label']) ? (string) $input['admin_label'] : '';
            $element = isset($input['element']) ? (string) $input['element'] : '';

            if (preg_match('/^([^\[\]]+)\[([^\[\]]+)\]$/', $key, $m) === 1) {
                [, $parent, $child] = $m;

                if (!isset($byName[$parent])) {
                    $byName[$parent] = [
                        'name' => $parent,
                        'element' => '',
                        'label' => $parent,
                        'children' => [],
                    ];
                }

                // The parser rewrites a child label to `parent[Child Label]`;
                // recover the readable part.
                if (preg_match('/^[^\[\]]+\[(.*)\]$/', $label, $lm) === 1 && $lm[1] !== '') {
                    $label = $lm[1];
                }

                $byName[$parent]['children'][$child] = $label !== '' ? $label : $child;
                continue;
            }

            if (str_contains($key, '[') || $key === '') {
                continue;
            }

            $byName[$key] = [
                'name' => $key,
                'element' => $element,
                'label' => $label !== '' ? $label : $key,
                'children' => $byName[$key]['children'] ?? [],
            ];
        }

        return array_values($byName);
    }
}
