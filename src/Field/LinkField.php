<?php

namespace DataKit\DataViews\Field;

/**
 * Represents a field that renders an `<a href` link.
 * @since $ver$
 */
final class LinkField extends Field {
	/**
	 * The available link types.
	 * @since $ver$
	 */
	private const TYPE_NONE = 'none';
	private const TYPE_FIELD = 'field';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $render = 'datakit_fields.link';


	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function default_context() : array {
		return [
			'link'           => null,
			'type'           => self::TYPE_NONE,
			'use_new_window' => true,
		];
	}

	/**
	 * Creates a link that uses the current field as the label, and a different field_id as the target link.
	 * @since $ver$
	 *
	 * @param string $field_id
	 *
	 * @return self
	 */
	public function linkToField( string $field_id ) : self {
		$clone                  = clone $this;
		$clone->context['type'] = self::TYPE_FIELD;
		$clone->context['link'] = $field_id;

		return $clone;
	}

	/**
	 * Whether to have the link open on a new window.
	 * @since $ver$
	 * @return self
	 */
	public function on_new_window() : self {
		$clone                            = clone $this;
		$clone->context['use_new_window'] = true;

		return $clone;
	}

	/**
	 * Whether to have the link open on the same window.
	 * @since $ver$
	 * @return self
	 */
	public function on_same_window() : self {
		$clone                            = clone $this;
		$clone->context['use_new_window'] = false;

		return $clone;
	}
}
