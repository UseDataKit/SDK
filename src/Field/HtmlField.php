<?php

namespace DataKit\DataViews\Field;

/**
 * Represents a field that is rendered as raw HTML.
 * @since $ver$
 */
final class HtmlField extends Field {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $render = 'datakit_fields.html';
}
