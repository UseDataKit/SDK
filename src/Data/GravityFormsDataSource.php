<?php

namespace DataKit\DataView\Data;

use DataKit\DataView\DataView\Operator;
use GFAPI;
use RuntimeException;
use WP_Error;

/**
 * Data source backed by a Gravity Forms form.
 * @since $ver$
 */
final class GravityFormsDataSource extends BaseDataSource {
	/**
	 * Fields that are top-level search keys.
	 * @since $ver$
	 * @var string[]
	 */
	private static array $top_level_filters = [ 'status', 'start_date', 'end_date' ];

	/**
	 * The form object.
	 * @since $ver$
	 * @var array[]
	 */
	private array $form;

	/**
	 * Microcache for the "current" entries.
	 * @since $ver$
	 * @var array[]
	 */
	private array $entries;

	/**
	 * Creates the data source.
	 * @since $ver$
	 *
	 * @param int $form_id The form ID.
	 */
	public function __construct( int $form_id ) {
		$form = GFAPI::get_form( $form_id );
		if ( ! is_array( $form ) ) {
			throw new RuntimeException( 'Form not found' );
		}

		$this->form = $form;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function id() : string {
		return sprintf( 'gravity-forms-%d', $this->form['id'] );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function name() : string {
		return sprintf( 'Gravity Forms (#%d) %s', $this->form['id'], $this->form['title'] );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_ids( int $limit = 100, int $offset = 0 ) : array {
		$entries = GFAPI::get_entries(
			$this->form['id'],
			$this->get_search_criteria(),
			$this->get_sorting(),
			[
				'offset'    => $offset,
				'page_size' => $limit,
			] );

		if ( $entries instanceof WP_Error ) {
			return [];
		}

		// Microcache entries on their ID.
		$this->entries = array_column( $entries, null, 'id' );

		// Return the ID's for the current set.
		return array_column( $entries, 'id' );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_by_id( string $id ) : array {
		$entry = $this->entries[ $id ] ?? GFAPI::get_entry( $id );
		if ( ! is_array( $entry ) ) {
			return [];
		}

		return $entry;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function count() : int {
		return GFAPI::count_entries( $this->form['id'], $this->get_search_criteria() );
	}

	/**
	 * Returns the search criteria based on the filters.
	 * @since $ver$
	 *
	 * @return array {field_filters: array} The search criteria.
	 */
	private function get_search_criteria() : array {
		if ( ! $this->filters ) {
			return [];
		}

		$filters                  = $this->top_level_filters();
		$filters['field_filters'] = array_filter(
			array_map(
				\Closure::fromCallable( [ $this, 'transform_filter_to_field_filter' ] ),
				$this->filters->to_array()
			)
		);

		return $filters;
	}

	/**
	 * Transforms a filter into a Gravity Forms field filter.
	 * @since $ver$
	 *
	 * @param array $filter The filter.
	 *
	 * @return null|array{key: string, value:string|int|float|array, operator:string} The field filter criteria.
	 */
	private function transform_filter_to_field_filter( array $filter ) : ?array {
		if ( in_array( $filter['field'], [ 'status', 'start_date', 'end_date' ], true ) ) {
			return null;
		}

		return [
			'key'      => $filter['field'],
			'value'    => $filter['value'],
			'operator' => $this->map_operator( $filter['operator'] ),
		];
	}

	/**
	 * Maps the field operator to a Gravity Forms search operator.
	 * @since $ver$
	 *
	 * @param string $operator The field operator.
	 *
	 * @return string The Gravity Forms search operator.
	 */
	private function map_operator( string $operator ) : string {
		$case = Operator::tryFrom( $operator );

		$lookup = [
			(string) Operator::is()     => 'IS',
			(string) Operator::isNot()  => 'IS NOT',
			(string) Operator::isAny()  => 'IN',
			(string) Operator::isNone() => 'NOT IN',
		];

		return $lookup[ (string) $case ] ?? 'CONTAINS';
	}

	/**
	 * Returns the top level filters for the Gravity Forms API.
	 * @since $ver$
	 * @return string[] The filters.
	 */
	private function top_level_filters() : array {
		$filters = [ 'status' => 'active' ];

		foreach ( $this->filters->to_array() as $filter ) {
			if ( ! in_array( $filter['field'], self::$top_level_filters, true ) ) {
				continue;
			}

			$filters[ $filter['field'] ] = $filter['value'];
		}

		return $filters;
	}

	private function get_sorting() : array {
		if ( ! $this->sort ) {
			return [];
		}
		$sort = $this->sort->to_array();

		return [
			'key'       => $sort['field'],
			'direction' => $sort['direction'],
		];
	}
}
