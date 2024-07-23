<?php

namespace DataKit\DataViews\DataView;

use DataKit\DataViews\Field\Field;

/**
 * Value object that represents a single data result.
 *
 * @since $ver$
 */
final class DataItem {
	/**
	 * The fields.
	 *
	 * @since $ver$
	 *
	 * @var Field[]
	 */
	private array $fields;

	/**
	 * The provided data.
	 *
	 * @since $ver$
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Creates the data item.
	 *
	 * @since $ver$
	 *
	 * @param array $fields The fields.
	 * @param array $data   The data.
	 */
	private function __construct( array $fields, array $data ) {
		$this->add_fields( ...$fields );

		$this->data = $data;
	}

	/**
	 * Adds the fields, and ensures validity.
	 *
	 * @since $ver$
	 *
	 * @param Field ...$fields The fields.
	 */
	private function add_fields( Field ...$fields ): void {
		$this->fields = $fields;
	}

	/**
	 * Creates an instance form an array.
	 *
	 * @since $ver$
	 *
	 * @param array $data_item The array.
	 *
	 * @return self The instance.
	 */
	public static function from_array( array $data_item ): self {
		return new self(
			$data_item['fields'] ?? [],
			$data_item['data'] ?? [],
		);
	}

	/**
	 * Serializes the instance to an array.
	 *
	 * @since $ver$
	 *
	 * @return array The array.
	 */
	public function to_array(): array {
		return [
			'fields' => $this->fields,
			'data'   => $this->data,
		];
	}

	/**
	 * Returns the fields for the data item.
	 *
	 * @since $ver$
	 *
	 * @return Field[] The fields.
	 */
	public function fields(): array {
		return $this->fields;
	}

	/**
	 * Returns the data for the data item.
	 *
	 * @since $ver$
	 *
	 * @return array The data.
	 */
	public function data(): array {
		return $this->data;
	}
}
