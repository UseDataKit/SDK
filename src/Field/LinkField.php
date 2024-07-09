<?php

namespace DataKit\DataViews\Field;

/**
 * Represents a field that renders an `<a href` link.
 *
 * @since $ver$
 */
final class LinkField extends Field {
	/**
	 * The available link types.
	 *
	 * @since $ver$
	 */
	private const TYPE_NONE = 'none';
	private const TYPE_FIELD = 'field';

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $render = 'datakit_fields.html';


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
	 *
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
	 *
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
	 *
	 * @since $ver$
	 * @return self
	 */
	public function on_same_window() : self {
		$clone                            = clone $this;
		$clone->context['use_new_window'] = false;

		return $clone;
	}


	/**
	 * Returns a link based on the context settings.
	 *
	 * @since $ver$
	 *
	 * @param array $data The item data.
	 *
	 * @return string The value.
	 */
	public function get_value( array $data ) {
		return sprintf(
			'<a href="%s" target="%s">%s</a>',
			esc_attr( $this->href( $data ) ),
			esc_attr( $this->target() ),
			$this->label( $data ),
		);
	}

	/**
	 * Return the href for the link.
	 *
	 * @since $ver$
	 *
	 * @param array $data The data object.
	 *
	 * @return string The href.
	 */
	private function href( array $data ) : string {
		if ( self::TYPE_FIELD === ( $this->context['type'] ?? self::TYPE_NONE ) ) {
			return $data[ $this->context['link'] ?? '' ] ?? '';
		}

		return parent::get_value( $data ) ?? '';
	}

	/**
	 * Returns the target for the link.
	 *
	 * @since $ver$
	 * @return string
	 */
	private function target() : string {
		return $this->context['use_new_window'] ?? false ? '_blank' : '_self';
	}

	/**
	 * Returns the label of the link.
	 *
	 * @since $ver$
	 *
	 * @param array $data THe data object.
	 *
	 * @return string The label.
	 */
	private function label( array $data ) : string {
		return parent::get_value( $data ) ?? '';
	}
}
