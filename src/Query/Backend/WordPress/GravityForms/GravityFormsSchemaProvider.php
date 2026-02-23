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
final class GravityFormsSchemaProvider
{
    /**
     * Describe the schema for a given scope (form_ids).
     */
    public function describe(array $scope): BackendSchema
    {
        $formIds = $scope['form_id'] ?? $scope['form_ids'] ?? [];

        if (!is_array($formIds)) {
            $formIds = [$formIds];
        }

        $fields = $this->getSystemFields();

        foreach ($formIds as $formId) {
            $formFields = $this->discoverFormFields((int) $formId);
            $fields = array_merge($fields, $formFields);
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
                aggregatable: true, timezone: 'utc',
            ),
            new FieldSchema('date_updated', 'Date Updated', ColumnType::Datetime, $allOps, timezone: 'utc'),
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
            new FieldSchema('form_title', 'Form Title', ColumnType::String, $allOps,
                description: 'Title of the form (joined from wp_gf_form).',
            ),
            // Semantic aliases — resolve to the same SQL columns as the canonical names.
            new FieldSchema('created_at', 'Created At', ColumnType::Datetime, $allOps,
                aggregatable: true, timezone: 'utc',
                description: 'Alias for date_created.',
            ),
            new FieldSchema('updated_at', 'Updated At', ColumnType::Datetime, $allOps,
                timezone: 'utc',
                description: 'Alias for date_updated.',
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
        if (!class_exists('\GFAPI')) {
            return [];
        }

        $form = \GFAPI::get_form($formId);

        if (!$form || !isset($form['fields'])) {
            return [];
        }

        $fields = [];
        $allOps = ComparisonOperator::cases();

        foreach ($form['fields'] as $field) {
            $fieldId = (string) ($field->id ?? '');
            $label = $field->label ?? 'Field ' . $fieldId;
            $type = $field->type ?? 'text';

            // Multi-input fields (name, address) — expand sub-fields
            if ($this->isMultiInputField($type) && !empty($field->inputs)) {
                foreach ($field->inputs as $input) {
                    $inputId = (string) ($input['id'] ?? '');
                    $inputLabel = $label . ' (' . ($input['label'] ?? $inputId) . ')';

                    $fields[] = new FieldSchema(
                        $inputId,
                        $inputLabel,
                        ColumnType::String,
                        $allOps,
                        description: "Form field: {$type} (sub-input)",
                    );
                }

                continue;
            }

            $columnType = $this->inferColumnType($type);
            $enumValues = null;

            if (in_array($type, ['select', 'radio', 'multiselect', 'checkbox'], true) && !empty($field->choices)) {
                $enumValues = [];
                foreach ($field->choices as $choice) {
                    $enumValues[$choice['value'] ?? $choice['text']] = $choice['text'] ?? $choice['value'];
                }
            }

            $fields[] = new FieldSchema(
                $fieldId,
                $label,
                $columnType,
                $allOps,
                enumValues: $enumValues,
                description: "Form field: {$type}",
                aggregatable: $columnType === ColumnType::Float || $columnType === ColumnType::Integer,
            );
        }

        return $fields;
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
