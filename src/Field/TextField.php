<?php

namespace DataKit\DataViews\Field;

/**
 * Represents a field that is rendered as normal text.
 *
 * @todo  Add size and weight
 *
 * @since $ver$
 */
final class TextField extends Field {
	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $render = 'datakit_fields.text';
}
