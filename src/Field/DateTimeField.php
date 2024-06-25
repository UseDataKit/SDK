<?php

namespace DataKit\DataViews\Field;

/**
 * Represents a field that renders a date and time.
 * @since $ver$
 */
final class DateTimeField extends Field {
	/**
	 * @inheritDoc
	 * @since $ver
	 */
	protected string $render = 'datakit_fields.datetime';
}
