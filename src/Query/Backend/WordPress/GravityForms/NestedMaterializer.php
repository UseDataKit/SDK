<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\GravityForms;

/**
 * Materializes nested (list) field values into a flat lookup table for JOINing.
 *
 * Gravity Forms list fields store serialized arrays in entry_meta. This materializer
 * creates a denormalized table that can be efficiently JOINed for unnesting.
 *
 * Table: wp_{prefix}gf_nested_{form_id}
 * Columns: id, entry_id, field_id, row_index, column_key, cell_value
 *
 * @since $ver$
 */
final class NestedMaterializer
{
    /**
     * Get the nested table name for a form.
     */
    public function tableName(int $formId): string
    {
        global $wpdb;

        return $wpdb->prefix . 'gf_nested_' . $formId;
    }

    /**
     * Check if the materialized table is stale (needs rebuild).
     */
    public function checkStaleness(int $formId): bool
    {
        global $wpdb;

        $table = $this->tableName($formId);

        // Check if table exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $table),
        );

        if ($exists === null) {
            return true;
        }

        // Check if entries have been updated since last materialization
        $lastMaterialized = get_option("gf_nested_last_{$formId}", '0');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $lastUpdated = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(date_updated) FROM {$wpdb->prefix}gf_entry WHERE form_id = %d AND status = 'active'",
                $formId,
            ),
        );

        return $lastUpdated > $lastMaterialized;
    }

    /**
     * Materialize nested fields for a form.
     *
     * @param int   $formId   The form ID.
     * @param int[] $fieldIds The list field IDs to materialize.
     */
    public function materialize(int $formId, array $fieldIds): void
    {
        global $wpdb;

        $table = $this->tableName($formId);

        // Create table if not exists
        $this->createTable($table);

        // Truncate existing data
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->query("TRUNCATE TABLE {$table}");

        // Fetch all list field meta for active entries
        $fieldPlaceholders = implode(', ', array_fill(0, count($fieldIds), '%d'));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $metas = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT em.entry_id, em.meta_key AS field_id, em.meta_value
                 FROM {$wpdb->prefix}gf_entry_meta AS em
                 INNER JOIN {$wpdb->prefix}gf_entry AS e ON e.id = em.entry_id
                 WHERE e.form_id = %d AND e.status = 'active'
                   AND em.meta_key IN ({$fieldPlaceholders})",
                $formId,
                ...$fieldIds,
            ),
            ARRAY_A,
        );

        if (!is_array($metas)) {
            return;
        }

        // Insert materialized rows
        foreach ($metas as $meta) {
            $value = maybe_unserialize($meta['meta_value']);

            if (!is_array($value)) {
                continue;
            }

            foreach ($value as $rowIndex => $row) {
                if (is_array($row)) {
                    foreach ($row as $colKey => $cellValue) {
                        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                        $wpdb->insert($table, [
                            'entry_id' => $meta['entry_id'],
                            'field_id' => $meta['field_id'],
                            'row_index' => $rowIndex,
                            'column_key' => $colKey,
                            'cell_value' => $cellValue,
                        ]);
                    }
                } else {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                    $wpdb->insert($table, [
                        'entry_id' => $meta['entry_id'],
                        'field_id' => $meta['field_id'],
                        'row_index' => $rowIndex,
                        'column_key' => '',
                        'cell_value' => (string) $row,
                    ]);
                }
            }
        }

        update_option("gf_nested_last_{$formId}", gmdate('Y-m-d H:i:s'));
    }

    private function createTable(string $table): void
    {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query("CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            entry_id BIGINT UNSIGNED NOT NULL,
            field_id MEDIUMINT UNSIGNED NOT NULL,
            row_index SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            column_key VARCHAR(100) NOT NULL DEFAULT '',
            cell_value LONGTEXT,
            PRIMARY KEY (id),
            KEY idx_entry_field (entry_id, field_id),
            KEY idx_field_value (field_id, cell_value(191))
        ) {$charset}");
    }
}
