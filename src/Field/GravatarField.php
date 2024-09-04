<?php

namespace DataKit\DataViews\Field;

/**
 * A Gravatar field.
 *
 * @since $ver$
 *
 * @mixin ImageField
 */
final class GravatarField extends Field {
	/**
	 * The default image types.
	 *
	 * @since $ver$
	 */
	private const DEFAULT_TYPES = [ '404', 'mp', 'identicon', 'monsterid', 'wavatar', 'retro', 'robohash', 'blank' ];

	/**
	 * The rating types.
	 *
	 * @since $ver$
	 */
	private const RATING_TYPES = [ 'g', 'pg', 'r', 'x' ];

	/**
	 * The composed image field.
	 *
	 * @since $ver$
	 *
	 * @var ImageField
	 */
	private ImageField $image;

	/**
	 * The image size.
	 *
	 * @since $ver$
	 *
	 * @var int
	 */
	private int $size = 80;

	/**
	 * The default image to use.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $default_image = 'mp';

	/**
	 * The default rating to use.
	 *
	 * @since $ver$
	 *
	 * @var string
	 */
	private string $rating = 'g';

	/**
	 * Returns the callback used on the image field.
	 *
	 * @since $ver$
	 *
	 * @return callable The callable.
	 */
	private function generate_callback(): callable {
		return fn( string $id, array $data ) => ( $data[ $id ] ?? null )
			? sprintf(
				'https://gravatar.com/avatar/%s?size=%d&default=%s&rating=%s',
				md5( $data[ $id ] ),
				$this->size,
				$this->default_image,
				$this->rating,
			)
			: '';
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public static function create( ...$args ): self {
		$self        = parent::create( ...$args );
		$self->image = ImageField::create( ...$args );

		return $self;
	}

	/**
	 * Returns an instance of the field with a specific image size.
	 *
	 * @since $ver$
	 *
	 * @param int $size The image size.
	 *
	 * @return self A field instance with the provided size.
	 */
	public function resolution( int $size ): self {
		if ( $size < 1 ) {
			$size = 1;
		}

		if ( $size > 2048 ) {
			$size = 2048;
		}

		$clone       = clone $this;
		$clone->size = $size;

		return clone $clone;
	}

	/**
	 * Returns an instance of the field with a specific default image fallback
	 *
	 * @since $ver$
	 *
	 * @return self A field instance with the provided default image.
	 */
	public function default_image( string $type ): self {
		$clone                = clone $this;
		$clone->default_image = in_array( $type, self::DEFAULT_TYPES, true ) ? $type : 'mp';

		return $clone;
	}

	/**
	 * Returns an instance of the field with a specific rating
	 *
	 * @since $ver$
	 *
	 * @return self A field instance with the provided rating.
	 */
	public function rating( string $rating ): self {
		$clone         = clone $this;
		$clone->rating = in_array( $rating, self::RATING_TYPES, true ) ? $rating : 'g';

		return $clone;
	}

	/**
	 * Any method calls on the field are forwarded to the ImageField.
	 *
	 * @since $ver$
	 *
	 * @param string $name      The method name.
	 * @param array  $arguments The arguments for the method.
	 *
	 * @return self
	 */
	public function __call( string $name, array $arguments ): self {
		$clone        = clone $this;
		$clone->image = $clone->image->{$name}( ...$arguments );

		return $clone;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_value( array $data ) {
		return $this->image
			->callback( $this->generate_callback() )
			->get_value( $data );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function render(): string {
		return isset( $this->image ) ? $this->image->render() : parent::render();
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function is_media_field(): bool {
		return isset( $this->image ) ? $this->image->is_media_field() : parent::is_media_field();
	}
}
