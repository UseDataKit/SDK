<?php

namespace DataKit\DataViews\Field;

/**
 * Represents a field that is rendered as a raw HTML.
 *
 * @since $ver$
 */
final class HtmlField extends Field {
	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $render = 'datakit_fields.html';
}
