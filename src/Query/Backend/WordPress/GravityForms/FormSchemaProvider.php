<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Backend\WordPress\GravityForms;

use DataKit\DataViews\Query\Engine\BackendSchema;

/**
 * Supplies the queryable schema for a Gravity Forms scope.
 *
 * The backend resolves a per-form field by stripping
 * {@see GravityFormsBackend::FIELD_PREFIX} off the key and using the remainder
 * as a `gf_entry_meta.meta_key`, so an implementation must key form fields
 * `field:<field id>` for them to be both advertised and queryable.
 *
 * @since $ver$
 */
interface FormSchemaProvider
{
    /**
     * Describe the schema for a source scope.
     *
     * @param array $scope Source scope, carrying `form_ids` or `form_id`.
     */
    public function describe(array $scope): BackendSchema;
}
