<?php

namespace DataKit\DataViews\Data;

use Closure;
use DataKit\DataViews\Data\Exception\DataSourceNotFoundException;
use DataKit\DataViews\DataView\Operator;
use GF_Field;
use GFAPI;
use GFExport;
use WP_Error;

/**
 * Data source backed by a Gravity Forms form.
 *
 * @since $ver$
 */
final class GravityFormsDataSource extends BaseDataSource {
	/**
	 * Fields that are top-level search keys.
	 *
	 * Note: These filters are handled differently by the Gravity Forms search API.
	 *
	 * @since $ver$
	 *
	 * @var string[]
	 */
	private static array $top_level_filters = [ 'status', 'start_date', 'end_date' ];

	/**
	 * The form object.
	 *
	 * @since $ver$
	 *
	 * @var array<string|int, mixed>
	 */
	private array $form;

	/**
	 * Microcache for the "current" entries.
	 *
	 * @since $ver$
	 *
	 * @var array[]
	 */
	private array $entries;

	/**
	 * Microcache for the data source fields.
	 *
	 * @since $ver$
	 *
	 * @var array<string, string>
	 */
	private array $fields;

	/**
	 * Creates the data source.
	 *
	 * @since $ver$
	 *
	 * @param int $form_id The form ID.
	 */
	public function __construct( int $form_id ) {
		$form = GFAPI::get_form( $form_id );
		if ( ! is_array( $form ) ) {
			throw new DataSourceNotFoundException( sprintf( 'Gravity Forms data source (%d) not found', $form_id ) );
		}

		$this->form = GFExport::add_default_export_fields( $form );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function id(): string {
		return sprintf( 'gravity-forms-%d', $this->form['id'] );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get_data_ids( int $limit = 100, int $offset = 0 ): array {
		$entries = GFAPI::get_entries(
			$this->form['id'],
			$this->get_search_criteria(),
			$this->get_sorting(),
			[
				'offset'    => $offset,
				'page_size' => $limit,
			],
		);

		if ( $entries instanceof WP_Error ) {
			return [];
		}

		// Microcache entries on their ID.
		$this->entries = array_column( $entries, null, 'id' );

		// Return IDs for the current set.
		return array_column( $entries, 'id' );
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get_data_by_id( string $id ): array {
		$entry = $this->entries[ $id ] ?? GFAPI::get_entry( (int) $id );

		if ( ! is_array( $entry ) ) {
			return [];
		}

		foreach ( $this->get_form_fields() as $field ) {
			// Returns the values for the entire field, as well as all sub input separately (e.g., 1, 1.1, 1.2, etc.).
			$inputs = [ $field->id, ...array_column( $field->inputs ?? [], 'id' ) ];

			foreach ( $inputs as $input_id ) {
				$entry[ $input_id ] = $field->get_value_export( $entry, $input_id );
			}
		}

		return $entry;
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function count(): int {
		return GFAPI::count_entries( $this->form['id'], $this->get_search_criteria() );
	}

	/**
	 * Returns the search criteria based on the filters.
	 *
	 * @since $ver$
	 *
	 * @return array The search criteria.
	 */
	private function get_search_criteria(): array {
		if ( ! $this->filters && ! $this->search ) {
			return [];
		}

		$filters = [];

		if ( $this->filters ) {
			$filters                  = $this->top_level_filters();
			$filters['field_filters'] = array_merge(
				...array_filter(
                    array_map(
                        Closure::fromCallable( [ $this, 'transform_filter_to_field_filters' ] ),
                        $this->filters->to_array(),
                    ),
                ),
			);
		}

		if ( $this->search ) {
			$filters['field_filters'] ??= [];

			$filters['field_filters'][] = [
				'field'    => '0', // All fields.
				'operator' => 'contains',
				'value'    => $this->search,
			];
		}

		return $filters;
	}

	/**
	 * Transforms a filter into a Gravity Forms field filters.
	 *
	 * Note: Returns an array of arrays, as a single DataView Filter can be comprised of multiple Gravity Forms filters.
	 *
	 * @since $ver$
	 *
	 * @param array $filter The filter.
	 *
	 * @return null|array{array{key: string, value:string|int|float|array, operator:string}} The field filter criteria.
	 */
	private function transform_filter_to_field_filters( array $filter ): ?array {
		if ( in_array( $filter['field'], self::$top_level_filters, true ) ) {
			return null;
		}

		return [
			[
				'key'      => $filter['field'],
				'value'    => $filter['value'],
				'operator' => $this->map_operator( $filter['operator'] ),
			],
		];
	}

	/**
	 * Maps the field operator to a Gravity Forms search operator.
	 *
	 * @todo  this needs to be fixed for all cases.
	 *
	 * @since $ver$
	 *
	 * @param string $operator The field operator.
	 *
	 * @return string The Gravity Forms search operator.
	 */
	private function map_operator( string $operator ): string {
		$case = Operator::try_from( $operator );

		$lookup = [
			(string) Operator::is()       => 'IS',
			(string) Operator::isNot()    => 'IS NOT',
			(string) Operator::isAny()    => 'IN',
			(string) Operator::isAll()    => 'IS',
			(string) Operator::isNotAll() => 'IN',
			(string) Operator::isNone()   => 'NOT IN',
		];

		return $lookup[ (string) $case ] ?? 'IS NOT';
	}

	/**
	 * Returns the top level filters for the Gravity Forms API.
	 *
	 * @since $ver$
	 *
	 * @return array<string, string> The filters.
	 */
	private function top_level_filters(): array {
		$filters = [];

		foreach ( $this->filters->to_array() as $filter ) {
			if ( ! in_array( $filter['field'], self::$top_level_filters, true ) ) {
				continue;
			}

			$filters[ $filter['field'] ] = $filter['value'];
		}

		return $filters;
	}

	/**
	 * Returns the sorting for Gravity Forms based on the sort object.
	 *
	 * @since $ver$
	 *
	 * @return array The Gravity Forms sorting.
	 */
	private function get_sorting(): array {
		if ( ! $this->sort ) {
			return [];
		}

		$sort = $this->sort->to_array();

		return [
			'key'       => $sort['field'],
			'direction' => $sort['direction'],
		];
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function get_fields(): array {
		if ( isset( $this->fields ) ) {
			return $this->fields;
		}

		$output = [];

		foreach ( $this->get_form_fields() as $field ) {
			$label = $field->get_field_label( true, '' );

			$output[ (string) $field->id ] = $label;

			if ( ! is_array( $field->inputs ) ) {
				continue;
			}

			foreach ( $field->inputs as $input ) {
				$key       = $input['id'] ?? null;
				$sub_label = $input['label'] ?? null;

				if ( ! isset( $key, $sub_label ) ) {
					continue;
				}

				$output[ $key ] = $sub_label;
			}
		}

		$this->fields = $output;

		return $output;
	}

	/**
	 * Returns the form fields.
	 *
	 * @since $ver$
	 *
	 * @return GF_Field[] The form fields.
	 */
	private function get_form_fields(): array {
		return array_filter(
			$this->form['fields'],
			static fn( $value ) => $value instanceof GF_Field,
		);
	}
}
