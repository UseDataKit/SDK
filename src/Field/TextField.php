<?php

namespace DataKit\DataViews\Field;

/**
 * Represents a field that is rendered as normal text.
 *
 * @since $ver$
 * @todo  add size and weight
 */
final class TextField extends Field {
	/**
	 * @inheritDoc
	 * @since $ver$
	 * @var string
	 */
	protected string $render = 'datakit_fields.text';
}
