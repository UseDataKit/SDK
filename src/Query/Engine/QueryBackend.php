<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query\Engine;

use DataKit\DataViews\Query\Query;

/**
 * Backend interface — all data sources implement this.
 *
 * @since $ver$
 */
interface QueryBackend
{
    /**
     * Source type identifier (e.g. "gravity_forms", "woocommerce", "array").
     */
    public function sourceType(): string;

    /**
     * @return Capability[] The capabilities this backend supports.
     */
    public function capabilities(): array;

    /**
     * Whether this backend supports a specific capability.
     */
    public function supports(Capability $cap): bool;

    /**
     * Describe the schema for the given scope.
     *
     * @param array $scope Source-specific scope (e.g. ["form_id" => [1]]).
     */
    public function describe(array $scope): BackendSchema;

    /**
     * Schema version for cache key versioning.
     * Bump when the schema changes to auto-invalidate cached results.
     */
    public function schemaVersion(): int;

    /**
     * Estimate the cost of executing a query.
     *
     * @return CostEstimate|null Null means "can't estimate."
     */
    public function estimate(Query $query): ?CostEstimate;

    /**
     * Compile a query into a backend-specific representation.
     */
    public function compile(Query $query, BackendSchema $schema): CompiledQuery;

    /**
     * Execute a compiled query.
     */
    public function execute(CompiledQuery $compiled): Result;

    /**
     * Whether this backend's dependencies are available in the current environment.
     */
    public static function isAvailable(): bool;
}
