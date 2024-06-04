<?php

namespace DataKit\DataView\DataView;

use DataKit\DataView\Field\Field;
use JsonException;

/**
 * Renderer that transforms a {@see DataView} into valid JSON.
 * @since $ver$
 */
final class JsonDataViewRenderer {
	public function render( DataView $data_view, bool $is_pretty = false ) : string {
		$output = [
			'dataSource'       => $data_view->data_source()->id(),
			'supportedLayouts' => $this->get_supported_layouts( $data_view ),
			'paginationInfo'   => $this->get_pagination_info( $data_view ),
			'view'             => $this->get_view_object( $data_view ),
			'fields'           => $this->get_fields_object( $data_view ),
			'data'             => $this->get_data_object( $data_view ),
		];

		try {
			$flags = JSON_THROW_ON_ERROR;
			if ( $is_pretty ) {
				$flags |= JSON_PRETTY_PRINT;
			}

			return json_encode( $output, $flags );
		} catch ( JsonException $e ) {
			return '{}';
		}
	}

	private function get_view_object( DataView $data_view ) : array {
		return [
			'search'       => '',
			'type'         => (string) $data_view->view(),
			'filters'      => $this->get_filters( $data_view ),
			'perPage'      => $data_view->per_page(),
			'page'         => $data_view->page(),
			'sort'         => $this->get_sort( $data_view ),
			'hiddenFields' => $this->get_hidden_fields( $data_view->fields() ),
			'layout'       => [],
		];
	}

	private function get_sort( DataView $data_view ) : ?array {
		if ( ! $data_view->sort() ) {
			return null;
		}

		return $data_view->sort()->to_array();
	}

	private function get_filters( DataView $data_view ) : ?array {
		if ( ! $data_view->filters() ) {
			return null;
		}

		return $data_view->filters()->to_array();
	}

	private function get_fields_object( DataView $data_view ) : array {
		$fields = [];
		foreach ( $data_view->fields() as $field ) {
			$fields[] = array_filter(
				$field->toArray(),
				static fn( $value, $key ) => ! is_null( $value ) && 'render' !== $key,
				ARRAY_FILTER_USE_BOTH,
			);
		}

		return $fields;
	}

	private function get_data_object( DataView $data_view ) : array {
		$data_source = $data_view->data_source();
		$object      = [];

		foreach ( $data_source->get_data_ids() as $data_id ) {
			$data = $data_source->get_data_by_id( $data_id );

			foreach ( $data_view->fields() as $field ) {
				$data[ $field->id() ] = $field->value( $data );
			}

			$object[] = $data;
		}

		return $object;
	}

	/**
	 * @param Field[] $fields
	 *
	 * @return string[] The field ID's.
	 */
	private function get_hidden_fields( array $fields ) : array {
		$hidden_fields = [];
		foreach ( $fields as $field ) {
			if ( ! $field->is_hidden() ) {
				continue;
			}
			$hidden_fields[] = $field->id();
		}

		return $hidden_fields;
	}

	private function get_supported_layouts( DataView $data_view ) : array {
		return [ (string) $data_view->view() ];
	}

	private function get_pagination_info( DataView $data_view ) : array {
		$total = $data_view->data_source()->count();

		return [
			'totalItems' => $total,
			'totalPages' => ceil( $total / $data_view->per_page() ),
		];
	}
}
