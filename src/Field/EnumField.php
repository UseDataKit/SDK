<?php

namespace DataKit\DataView\Field;

/**
 * @since $ver$
 */
final class EnumField extends Field {
	protected string $render = 'fields.enum';

	protected function __construct(
		protected string $id,
		protected string $header,
		array $elements,
		protected $is_sortable = true,
		protected $is_hideable = true,
		protected array $operators = [],
		protected $is_primary = true,
		protected ?string $default_value = null,
	) {
		parent::__construct( $id, $header, $is_sortable, $is_hideable, $operators, $is_primary, $default_value );
		$this->set_elements($elements);
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_elements() : array {
		return $this->elements;
	}
}
