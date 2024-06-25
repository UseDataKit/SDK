<?php

namespace DataKit\DataViews\Field;

/**
 * Represents a field that is rendered as an image.
 * @since $ver$
 */
final class ImageField extends Field {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected string $render = 'datakit_fields.image';

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
	 * @since $ver$
	 *
	 * @param int $width The width.
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
	 * @since $ver$
	 *
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
}
