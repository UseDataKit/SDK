<?php

namespace DataKit\DataView\DataView;

use DataKit\DataView\Field\Field;
use JsonException;

final class JsonDataViewRenderer {
	public function render( DataView $data_view ) : string {
		$output = [
			'view'   => $this->get_view_object( $data_view ),
			'fields' => $this->get_fields_object( $data_view ),
			'data'   => $this->get_data_object( $data_view ),
		];

		try {
			return json_encode( $output, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT );
		} catch ( JsonException ) {
			return '{}';
		}
	}

	private function get_view_object( DataView $data_view ) : array {
		return array_filter(
			[
				'type'         => $data_view->view(),
				'filters'      => $data_view->filters()?->to_array(),
				'perPage'      => $data_view->per_page(),
				'page'         => $data_view->page(),
				'sort'         => $data_view->sort()?->toArray(),
				'hiddenFields' => $this->get_hidden_fields( $data_view->fields() ),
			],
			static fn( $value ) : bool => ! empty( $value ) || $value === false
		);
	}

	private function get_fields_object( DataView $data_view ) : array {
		$fields = [];
		foreach ( $data_view->fields() as $field ) {
			$fields[] = array_filter(
				$field->toArray(),
				static fn( $value ) => ! empty( $value ) || $value === false,
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
}
