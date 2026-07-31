<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\FluentForms;

/**
 * Reads a Fluent Forms form definition as a normalized field list.
 *
 * Exists so the backend's field discovery can be exercised without a live
 * Fluent Forms install; production always uses
 * {@see FluentFormsApiFormRepository}.
 *
 * The shape is normalized here rather than passing Fluent Forms' raw
 * `form_fields` JSON through, because that JSON nests container fields
 * (name, address) inside a per-field `fields` map and the flattening rules
 * live in Fluent Forms' own parser. Keeping the seam normalized means the
 * backend never re-implements that walk.
 *
 * @since $ver$
 */
interface FluentFormsFormRepository
{
    /**
     * Return the form's answerable fields, or an empty list when the form is
     * unreadable.
     *
     * Each entry is:
     *
     *   - `name`     string  The input's `field_name` as stored in
     *                        `fluentform_entry_details`.
     *   - `element`  string  Fluent Forms element type (`input_text`,
     *                        `input_checkbox`, ...).
     *   - `label`    string  Human label (admin label preferred).
     *   - `children` array<string, string>  For container elements
     *                        (`input_name`, `address`): `sub_field_name` =>
     *                        label. Empty for plain fields.
     *
     * @param int $formId Fluent Forms form ID.
     *
     * @return array<int, array{name: string, element: string, label: string, children: array<string, string>}>
     */
    public function getFields(int $formId): array;
}
