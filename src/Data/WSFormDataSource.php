<?php

namespace DataKit\DataViews\Data;

use DataKit\DataViews\Data\Exception\DataSourceNotFoundException;
use DataKit\DataViews\Data\Exception\DataNotFoundException;
use DataKit\DataViews\DataView\Operator;

/**
 * Data source backed by a WS Form form.
 *
 * @since $ver$
 */
final class WSFormDataSource extends BaseDataSource implements MutableDataSource {
	/**
	 * The submit export object.
	 *
	 * @since $ver$
	 * @var \WS_Form_Submit_Export
	 */
	private \WS_Form_Submit_Export $ws_form_submit_export;

	/**
	 * Microcache for the "current" entries.
	 *
	 * @since $ver$
	 * @var array[]
	 */
	private array $entries;

	/**
	 * Microcache for the data source fields.
	 *
	 * @since $ver$
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
		try {
			$this->ws_form_submit_export = new \WS_Form_Submit_Export( $form_id );

		} catch ( Exception $e ) {
			throw new DataSourceNotFoundException( sprintf( 'WS Form data source (%d) not found', $form_id ) );
		}
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function id(): string {
		return sprintf( 'ws-form-%d', $this->ws_form_submit_export->form_id );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_data_ids( int $limit = 100, int $offset = 0 ): array {
		try {
			// Get submissions
			$entries = $this->ws_form_submit_export->get_rows(
				$limit,                   // Limit
				$offset,                  // Offset
				$this->get_keyword(),     // Keyword
				$this->get_filters(),     // Filters
				$this->get_order_by(),    // Order by
				$this->get_order(),       // Order
				true,                     // Bypass capabilities check
				false,                    // Clear hidden fields,
				false                     // Sanitize rows (DataKit already sanitizes data, set to false to prevent double escaping)
			);

		} catch (Exception $e) {
			throw new DataNotFoundException( $e->getMessage() );
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
	public function get_data_by_id( string $id ): array {
		if ( isset( $this->entries[ $id ] ) ) {
			return $this->entries[ $id ];

		} else {

			// Get row
			try {
				// Get submission by ID
				$entry = $this->ws_form_submit_export->get_row_by_id(
					$id,     // ID
					true,    // Bypass capabilities check
					false    // Clear hidden fields
				);

			} catch ( Exception $e ) {
				throw DataNotFoundException::with_id( $this, $id );
			}
		}

		if ( ! is_array( $entry ) ) {
			return [];
		}

		return $entry;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function count(): int {
		try {
			// Get row count
			return $this->ws_form_submit_export->get_row_count(
				self::get_keyword(),    // Keyword
				self::get_filters(),    // Filters
				true                    // Bypass capabilities check
			);

		} catch ( Exception $e ) {
			throw new DataNotFoundException( $e->getMessage() );
		}
	}

	/**
	 * Returns the keyword based on the filters.
	 *
	 * @since $ver$
	 *
	 * @return string The keyword.
	 */
	private function get_keyword(): string {
		if ( ! $this->search ) {
			return '';
		}

		return $this->search;
	}

	/**
	 * Returns the filters.
	 *
	 * @since $ver$
	 *
	 * @return array The filters.
	 */
	private function get_filters(): array {
		if ( ! $this->filters ) {
			return [];
		}

		// Get DataKit filters as array
		$filters = $this->filters->to_array();

		// Operator lookups (DataKit => WS Form)
		$datakit_to_wsform_operators = [

			(string) Operator::is()       => '==',
			(string) Operator::isNot()    => '!=',
			(string) Operator::isAny()    => 'in',
			(string) Operator::isAll()    => 'in',
			(string) Operator::isNotAll() => 'not_in',
			(string) Operator::isNone()   => 'not_in'
		];

		// Process each filter
		foreach ( $filters as $filter_index => $filter ) {
			if ( $datakit_to_wsform_operators[$filter['operator']] ?? false ) {
				$filters[$filter_index]['operator'] = $datakit_to_wsform_operators[$filter['operator']];
			}
		}

		return $filters;
	}

	/**
	 * Returns the order by based on the filters.
	 *
	 * @since $ver$
	 *
	 * @return string The order by.
	 */
	private function get_order_by(): string {
		if ( ! $this->sort ) {
			return 'id';
		}

		$sort_array = $this->sort->to_array();

		return $sort_array['field'];
	}

	/**
	 * Returns the order by based on the filters.
	 *
	 * @since $ver$
	 *
	 * @return string The order by.
	 */
	private function get_order(): string {
		if ( ! $this->sort ) {
			return 'DESC';
		}

		$sort_array = $this->sort->to_array();

		return $sort_array['direction'];
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_fields(): array {
		if ( isset( $this->fields ) ) {
			return $this->fields;
		}

		try {
			// Get header key => value array
			$this->fields = $this->ws_form_submit_export->get_header(
				true           // Bypass capabilities check
			);

		} catch (Exception $e) {
			throw new DataNotFoundException( $e->getMessage() );
		}

		return $this->fields;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function delete_data_by_id( string ...$ids ): void {
		foreach ( $ids as $id ) {

			try {
				// Move submission ID to trash
				$ws_form_submit = new \WS_Form_Submit();
				$ws_form_submit->id = $id;
				$ws_form_submit->db_delete(

					false,    // Permanently delete (false = Trash)
					true,     // Count update (Statistics)
					true      // Bypass capabilities check (Controlled by DataView deletable method)
				);

			} catch (Exception $e) {
				throw new DataNotFoundException( $e->getMessage() );
			}
		}
	}

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function can_delete(): bool {
		return true;
	}
}
