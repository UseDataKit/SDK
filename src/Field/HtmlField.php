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

	/**
	 * Returns an instance that allows scripts to be executed.
	 *
	 * @since $ver$
	 * @return self The field.
	 */
	public function allow_scripts(): self {
		$clone                                = clone $this;
		$clone->context['is_scripts_allowed'] = true;

		return $clone;
	}

	/**
	 * Returns an instance that removes scripts from the content.
	 *
	 * @since $ver$
	 * @return self The field.
	 */
	public function deny_scripts(): self {
		$clone                                = clone $this;
		$clone->context['is_scripts_allowed'] = false;

		return $clone;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function default_context(): array {
		return array_merge(
			parent::default_context(),
			[
				'is_scripts_allowed' => false,
			]
		);
	}
}
