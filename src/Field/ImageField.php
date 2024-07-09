<?php

namespace DataKit\DataViews\Field;

/**
 * Represents a field that is rendered as an image.
 *
 * @since $ver$
 */
final class ImageField extends Field {
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
		return array_merge( parent::default_context(), [
			'class'  => '',
			'width'  => null,
			'height' => null,
		] );
	}

	/**
	 * Returns an instance with a set width and height.
	 *
	 * @since $ver$
	 *
	 * @param int      $width  The width.
	 * @param int|null $height The (optional) height.
	 *
	 * @return self The field.
	 */
	public function size( int $width, ?int $height = null ) : self {
		$clone = clone $this;

		$clone->context['width']  = $width;
		$clone->context['height'] = $height;

		return $clone;
	}

	/**
	 * Returns an instance with a set class.
	 *
	 * @since $ver$
	 *
	 * @param string $class The class to set on the image.
	 *
	 * @return self The field.
	 */
	public function class( string $class ) : self {
		$clone                   = clone $this;
		$clone->context['class'] = $class;

		return $clone;
	}

	/**
	 * Returns an instance with a set alt text.
	 *
	 * @since $ver$
	 *
	 * @param string $alt The alt text.
	 *
	 * @return self the field.
	 */
	public function alt( string $alt ) : self {
		$clone                 = clone $this;
		$clone->context['alt'] = $alt;

		return $clone;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_value( array $data ) : string {
		$attributes = array_merge( $this->context(), [
			'src' => parent::get_value( $data ),
		] );

		$params = [];
		foreach ( array_filter( $attributes ) as $key => $value ) {
			$params[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
		}

		return sprintf( '<img %s />', implode( ' ', $params ) );
	}
}
