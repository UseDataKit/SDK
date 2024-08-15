<?php

namespace DataKit\DataViews\Field;

/**
 * Represents a field that is rendered as normal text.
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

	/**
	 * Returns an instance that adds break lines on the text.
	 *
	 * @since $ver$
	 *
	 * @return self The field.
	 */
	public function break(): self {
		$clone                   = clone $this;
		$clone->context['nl2br'] = true;

		return $clone;
	}

	/**
	 * Returns an instance that adds break lines on the text.
	 *
	 * @since $ver$
	 *
	 * @return self The field.
	 */
	public function inline(): self {
		$clone                   = clone $this;
		$clone->context['nl2br'] = false;

		return $clone;
	}

	/**
	 * Returns an instance with a specific font weight.
	 *
	 * @since $ver$
	 *
	 * @return self The field.
	 */
	public function weight( string $weight = '' ): self {
		$clone                    = clone $this;
		$clone->context['weight'] = $weight;

		return $clone;
	}

	/**
	 * Returns an instance which is italic or roman.
	 *
	 * @since $ver$
	 *
	 * @param bool $is_italic Whether the text should be italic.
	 *
	 * @return self The field.
	 */
	public function italic( bool $is_italic = true ): self {
		$clone                    = clone $this;
		$clone->context['italic'] = $is_italic;

		return $clone;
	}

	/**
	 * Returns an instance which is roman.
	 *
	 * @since $ver$
	 *
	 * @return self The field.
	 */
	public function roman(): self {
		return $this->italic( false );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function default_context(): array {
		return [
			'nl2br'  => false,
			'weight' => '',
			'italic' => false,
		];
	}
}
