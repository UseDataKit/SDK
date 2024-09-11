<?php

namespace DataKit\DataViews\Field;

use DataKit\DataViews\DataView\Operator;

/**
 * Represents an (immutable) field which is filterable.
 *
 * @since $ver$
 */
abstract class FilterableField extends Field {
	/**
	 * The filter operators.
	 *
	 * @since $ver$
	 *
	 * @var array
	 */
	protected array $operators = [];

	/**
	 * Whether the fields filter is a primary filter.
	 *
	 * @since $ver$
	 *
	 * @var bool
	 */
	protected bool $is_primary = false;

	/**
	 * Create a new instance with the provided filter operators.
	 *
	 * @since $ver$
	 *
	 * @param Operator ...$operators The operators.
	 *
	 * @return static A new instance with the filters applied.
	 */
	public function filterable_by( Operator ...$operators ) {
		$clone            = clone $this;
		$clone->operators = $operators;

		return $clone;
	}

	/**
	 * Returns the `filterBy` options for the JavaScript object.
	 *
	 * @since $ver$
	 *
	 * @return array|null The filter options.
	 */
	private function get_filter_by(): ?array {
		if ( ! $this->operators ) {
			return null;
		}

		return [
			'operators' => array_map(
				static fn( Operator $operator ): string => (string) $operator,
				$this->operators,
			),
			'isPrimary' => $this->is_primary,
		];
	}

	/**
	 * Returns a new instance of the field that is primary.
	 *
	 * Note: Filters for primary fields are always shown.
	 *
	 * @since $ver$
	 *
	 * @return self The field which is primary.
	 */
	public function primary() {
		$clone             = clone $this;
		$clone->is_primary = true;

		return $clone;
	}

	/**
	 * Returns a new instance of the field that is secondary.
	 *
	 * Note: Filters for secondary fields are hidden behind a dropdown.
	 *
	 * @since $ver$
	 *
	 * @return self The field which is primary.
	 */
	public function secondary() {
		$clone             = clone $this;
		$clone->is_primary = false;

		return $clone;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function to_array(): array {
		return array_merge(
            parent::to_array(),
            [
				'filterBy' => $this->get_filter_by(),
			]
        );
	}
}
