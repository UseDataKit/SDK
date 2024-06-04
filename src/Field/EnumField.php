<?php

namespace DataKit\DataView\Field;

/**
 * @since $ver$
 */
final class EnumField extends Field {
	protected string $render = 'fields.enum';
	protected string $id;
	protected string $header;
	protected $is_sortable;
	protected $is_hideable;
	protected array $operators;
	protected $is_primary;
	protected ?string $default_value = null;

	protected function __construct(
		string $id,
		string $header,
		array $elements,
		$is_sortable = true,
		$is_hideable = true,
		array $operators = [],
		$is_primary = true,
		?string $default_value = null
	) {
		$this->default_value = $default_value;
		$this->is_primary    = $is_primary;
		$this->operators     = $operators;
		$this->is_hideable   = $is_hideable;
		$this->is_sortable   = $is_sortable;
		$this->header        = $header;
		$this->id            = $id;

		parent::__construct( $id, $header, $is_sortable, $is_hideable, $operators, $is_primary, $default_value );

		$this->set_elements( $elements );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function get_elements() : array {
		return $this->elements;
	}
}
