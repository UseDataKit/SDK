<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\GravityForms;

use DataKit\DataViews\Query\ColumnType;
use DataKit\DataViews\Query\ComparisonOperator;
use DataKit\DataViews\Query\Engine\BackendSchema;
use DataKit\DataViews\Query\Engine\Capability;
use DataKit\DataViews\Query\Engine\FieldSchema;

/**
 * Schema provider for Gravity Forms — discovers form fields from the GF API.
 *
 * @since $ver$
 */
final class GravityFormsSchemaProvider implements FormSchemaProvider
{
    /**
     * Gravity Forms field types that hold no submitted answer.
     *
     * @var string[]
     */
    private const EXCLUDED_FIELD_TYPES = [
        'html', 'section', 'page', 'captcha', 'honeypot',
    ];

    private GravityFormsFormRepository $forms;

    public function __construct(?GravityFormsFormRepository $forms = null)
    {
        $this->forms = $forms ?? new GFAPIFormRepository();
    }

    /**
     * Describe the schema for a given scope (form_ids).
     */
    public function describe(array $scope): BackendSchema
    {
        $fields = $this->getSystemFields();

        foreach ($this->scopeFormIds($scope) as $formId) {
            foreach ($this->discoverFormFields($formId) as $field) {
                $fields[] = $field;
            }
        }

        return new BackendSchema(
            'gravity_forms',
            'Gravity Forms Entries',
            'Form submissions including payment data, metadata, and user-submitted fields.',
            $this->getCapabilities(),
            $fields,
        );
    }

    /**
     * @return int[]
     */
    private function scopeFormIds(array $scope): array
    {
        $formIds = $scope['form_ids'] ?? $scope['form_id'] ?? [];

        if (!is_array($formIds)) {
            $formIds = [$formIds];
        }

        $formIds = array_filter(
            array_map('intval', $formIds),
            static fn (int $id): bool => $id > 0,
        );

        return array_values(array_unique($formIds));
    }

    /**
     * @return FieldSchema[]
     */
    private function getSystemFields(): array
    {
        $allOps = ComparisonOperator::cases();

        return [
            new FieldSchema('entry_id', 'Entry ID', ColumnType::Integer, $allOps, aggregatable: true),
            new FieldSchema('form_id', 'Form ID', ColumnType::Integer, $allOps),
            new FieldSchema('status', 'Status', ColumnType::String, $allOps,
                enumValues: ['active' => 'Active', 'spam' => 'Spam', 'trash' => 'Trash'],
            ),
            new FieldSchema('date_created', 'Date Created', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc', aliases: ['created_at'],
            ),
            new FieldSchema('date_updated', 'Date Updated', ColumnType::Datetime, $allOps,
                timezone: 'utc', aliases: ['updated_at'],
            ),
            new FieldSchema('ip', 'IP Address', ColumnType::String, $allOps, sortable: false),
            new FieldSchema('source_url', 'Source URL', ColumnType::String, $allOps, sortable: false),
            new FieldSchema('currency', 'Currency', ColumnType::String, $allOps),
            new FieldSchema('payment_status', 'Payment Status', ColumnType::String, $allOps),
            new FieldSchema('payment_date', 'Payment Date', ColumnType::Datetime, $allOps, timezone: 'utc'),
            new FieldSchema('payment_amount', 'Payment Amount', ColumnType::Float, $allOps, aggregatable: true),
            new FieldSchema('payment_method', 'Payment Method', ColumnType::String, $allOps),
            new FieldSchema('transaction_id', 'Transaction ID', ColumnType::String, $allOps),
            new FieldSchema('created_by', 'Created By (User ID)', ColumnType::Integer, $allOps),
            new FieldSchema('is_starred', 'Starred', ColumnType::Boolean, $allOps),
            new FieldSchema('is_read', 'Read', ColumnType::Boolean, $allOps),
            new FieldSchema('user_agent', 'User Agent', ColumnType::String, $allOps, sortable: false),
            new FieldSchema('transaction_type', 'Transaction Type', ColumnType::String, $allOps),
            new FieldSchema('post_id', 'Post ID', ColumnType::Integer, $allOps),
            new FieldSchema('is_fulfilled', 'Fulfilled', ColumnType::Boolean, $allOps),
            new FieldSchema('form_title', 'Form title', ColumnType::String, $allOps,
                description: 'Title of the form (joined from wp_gf_form).',
            ),
        ];
    }

    /**
     * Discover fields from a Gravity Forms form definition.
     *
     * @return FieldSchema[]
     */
    private function discoverFormFields(int $formId): array
    {
        $form = $this->forms->getForm($formId);

        if ($form === null || !isset($form['fields']) || !is_iterable($form['fields'])) {
            return [];
        }

        $fields = [];
        $allOps = ComparisonOperator::cases();

        foreach ($form['fields'] as $field) {
            $fieldId = (string) ($this->property($field, 'id') ?? '');
            $type = (string) ($this->property($field, 'type') ?? 'text');

            if ($fieldId === '' || in_array($type, self::EXCLUDED_FIELD_TYPES, true)) {
                continue;
            }

            $label = (string) ($this->property($field, 'label') ?? ('Field ' . $fieldId));
            $inputs = $this->property($field, 'inputs');

            // Multi-input fields (name, address) store one meta row per input,
            // so the sub-input ID is the queryable key, not the parent ID.
            if ($this->isMultiInputField($type) && is_iterable($inputs)) {
                foreach ($inputs as $input) {
                    $inputId = (string) ($this->property($input, 'id') ?? '');

                    if ($inputId === '') {
                        continue;
                    }

                    $fields[] = new FieldSchema(
                        GravityFormsBackend::FIELD_PREFIX . $inputId,
                        $label . ' (' . ($this->property($input, 'label') ?? $inputId) . ')',
                        ColumnType::String,
                        $allOps,
                        description: "Form field: {$type} (sub-input)",
                    );
                }

                continue;
            }

            $columnType = $this->inferColumnType($type);

            $fields[] = new FieldSchema(
                GravityFormsBackend::FIELD_PREFIX . $fieldId,
                $label,
                $columnType,
                $allOps,
                enumValues: $this->enumValues($field, $type),
                description: "Form field: {$type}",
                aggregatable: in_array($columnType, [ColumnType::Float, ColumnType::Integer], true),
            );
        }

        return $fields;
    }

    /**
     * Read a property off a field definition.
     *
     * `GFAPI::get_form()` returns GF_Field objects, while a stored or filtered
     * form definition can carry plain arrays; both shapes reach here.
     *
     * @param mixed $definition Field or input definition.
     */
    private function property(mixed $definition, string $key): mixed
    {
        if (is_array($definition)) {
            return $definition[$key] ?? null;
        }

        if (is_object($definition)) {
            return $definition->{$key} ?? null;
        }

        return null;
    }

    /**
     * Choice values advertised for a choice-based field.
     *
     * @param mixed $definition Field definition.
     *
     * @return array<string, string>|null
     */
    private function enumValues(mixed $definition, string $type): ?array
    {
        $choices = $this->property($definition, 'choices');

        if (!in_array($type, ['select', 'radio', 'multiselect', 'checkbox'], true) || !is_iterable($choices)) {
            return null;
        }

        $values = [];

        foreach ($choices as $choice) {
            $value = $this->property($choice, 'value') ?? $this->property($choice, 'text');
            $text = $this->property($choice, 'text') ?? $value;

            if ($value === null) {
                continue;
            }

            $values[(string) $value] = (string) $text;
        }

        return $values === [] ? null : $values;
    }

    private function isMultiInputField(string $type): bool
    {
        return in_array($type, ['name', 'address', 'creditcard'], true);
    }

    private function inferColumnType(string $gfType): ColumnType
    {
        return match ($gfType) {
            'number', 'calculation', 'total', 'quantity', 'product', 'shipping' => ColumnType::Float,
            'date' => ColumnType::Datetime,
            'checkbox', 'consent' => ColumnType::Boolean,
            default => ColumnType::String,
        };
    }

    /**
     * @return Capability[]
     */
    private function getCapabilities(): array
    {
        return [
            Capability::FilterEq, Capability::FilterNeq,
            Capability::FilterGt, Capability::FilterGte,
            Capability::FilterLt, Capability::FilterLte,
            Capability::FilterIn, Capability::FilterNotIn,
            Capability::FilterBetween,
            Capability::FilterContains, Capability::FilterNotContains,
            Capability::FilterStartsWith,
            Capability::FilterIsEmpty, Capability::FilterIsNotEmpty,
            Capability::AggCount, Capability::AggSum, Capability::AggAvg,
            Capability::AggMin, Capability::AggMax, Capability::AggCountDistinct,
            Capability::GroupBy, Capability::Having, Capability::OrConditions,
            Capability::TimeBucket, Capability::Search, Capability::OrderBy,
            Capability::LimitOffset, Capability::Unnest,
        ];
    }
}
