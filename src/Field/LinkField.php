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
	private const TYPE_NONE  = 'none';
	private const TYPE_FIELD = 'field';

	/**
	 * Contains a custom label for the link.
	 *
	 * @since $ver$
	 * @var string|null
	 */
	private ?string $link_label = null;

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	protected string $render = 'datakit_fields.html';

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	protected function default_context(): array {
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
	 * @param string $field_id The ID of the field to link to.
	 *
	 * @return self
	 */
	public function link_to_field( string $field_id ): self {
		$clone                  = clone $this;
		$clone->context['type'] = self::TYPE_FIELD;
		$clone->context['link'] = $field_id;

		return $clone;
	}

	/**
	 * Returns whether to have the link open in a new window.
	 *
	 * @since $ver$
	 *
	 * @return self
	 */
	public function on_new_window(): self {
		$clone                            = clone $this;
		$clone->context['use_new_window'] = true;

		return $clone;
	}

	/**
	 * Returns whether to have the link open in the same window.
	 *
	 * @since $ver$
	 *
	 * @return self
	 */
	public function on_same_window(): self {
		$clone                            = clone $this;
		$clone->context['use_new_window'] = false;

		return $clone;
	}

	/**
	 * Applies a custom label to the link.
	 *
	 * @since $ver$
	 *
	 * @param string $link_label The label.
	 *
	 * @return self
	 */
	public function with_label( string $link_label ): self {
		$clone             = clone $this;
		$clone->link_label = $link_label;

		return $clone;
	}

	/**
	 * Removes a custom label from the link.
	 *
	 * @since $ver$
	 *
	 * @return self
	 */
	public function without_label(): self {
		$clone             = clone $this;
		$clone->link_label = null;

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
	public function get_value( array $data ): string {
		$href = self::safe_href( $this->href( $data ) );

		if ( ! $href ) {
			return '';
		}

		return sprintf(
			'<a href="%s" target="%s">%s</a>',
			esc_attr( $href ),
			esc_attr( $this->target() ),
			esc_html( $this->link_label( $data ) ),
		);
	}

	/**
	 * Schemes a link may use.
	 *
	 * @since $ver$
	 *
	 * @var string[]
	 */
	private const ALLOWED_SCHEMES = [ 'http', 'https', 'mailto', 'tel', 'ftp', 'ftps' ];

	/**
	 * Returns the href if its scheme is one a link may safely use.
	 *
	 * An href arrives in row data, so its scheme is submitted content.
	 * `esc_attr()` makes a value safe to sit inside a quoted attribute and
	 * says nothing about what the attribute then does: `javascript:alert(1)`
	 * contains no character it touches. Escaping and scheme filtering are
	 * different jobs and only the first was being done.
	 *
	 * Whitespace and control characters are removed before the scheme is
	 * read, because browsers ignore them when parsing a URL — `java\tscript:`
	 * is a working javascript URL that a leading-trim would pass through.
	 *
	 * @since $ver$
	 *
	 * @param string $href The candidate href.
	 *
	 * @return string The href, or an empty string if it may not be linked.
	 */
	private static function safe_href( string $href ): string {
		$stripped = preg_replace( '/[\x00-\x20\x7F]+/', '', $href ) ?? '';

		if ( '' === $stripped ) {
			return '';
		}

		// No scheme means a relative or protocol-relative URL, which cannot
		// carry one.
		if ( ! preg_match( '/^([A-Za-z][A-Za-z0-9+.\-]*):/', $stripped, $matches ) ) {
			return $href;
		}

		return in_array( strtolower( $matches[1] ), self::ALLOWED_SCHEMES, true ) ? $href : '';
	}

	/**
	 * Returns the href for the link.
	 *
	 * @since $ver$
	 *
	 * @param array $data The data object.
	 *
	 * @return string The href.
	 */
	private function href( array $data ): string {
		if ( self::TYPE_FIELD === ( $this->context['type'] ?? self::TYPE_NONE ) ) {
			return $data[ $this->context['link'] ?? '' ] ?? '';
		}

		return parent::get_value( $data ) ?? '';
	}

	/**
	 * Returns the target for the link.
	 *
	 * @since $ver$
	 *
	 * @return string
	 */
	private function target(): string {
		return $this->context['use_new_window'] ?? false ? '_blank' : '_self';
	}

	/**
	 * Returns the label of the link.
	 *
	 * @since $ver$
	 *
	 * @param array $data The data object.
	 *
	 * @return string The label.
	 */
	private function link_label( array $data ): string {
		if ( null === $this->link_label ) {
			return parent::get_value( $data ) ?? '';
		}

		return self::apply_merge_tags( $this->link_label, $data );
	}
}
