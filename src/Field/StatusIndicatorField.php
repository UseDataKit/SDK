<?php

namespace DataKit\DataViews\Field;

use InvalidArgumentException;

/**
 * A field that represents a status indicator.
 *
 * @since $ver$
 */
final class StatusIndicatorField extends Field {
	/**
	 * The indicator types.
	 *
	 * @since $ver$
	 */
	private const TYPE_BOOLEAN = 'boolean';
	private const TYPE_MAPPING = 'mapping';

	/**
	 * The indicator statuses.
	 *
	 * @since $ver$
	 */
	public const STATUS_ACTIVE   = 'active';
	public const STATUS_INACTIVE = 'inactive';
	public const STATUS_INFO     = 'info';
	public const STATUS_WARNING  = 'warning';
	public const STATUS_ERROR    = 'error';

	/**
	 * The default labels per status.
	 *
	 * @since $ver$
	 * @var array|string[]
	 */
	private array $labels = [
		self::STATUS_ACTIVE   => 'Active',
		self::STATUS_INACTIVE => 'Inactive',
		self::STATUS_INFO     => 'Info',
		self::STATUS_WARNING  => 'Warning',
		self::STATUS_ERROR    => 'Error',
	];

	/**
	 * The indicator type.
	 *
	 * @since $ver$
	 * @var string
	 */
	private string $type = self::TYPE_BOOLEAN;

	/**
	 * Whether the indicator has a dot.
	 *
	 * @since $ver$
	 * @var bool
	 */
	private bool $has_dot = true;

	/**
	 * Whether the indicator is a pill.
	 *
	 * @since $ver$
	 * @var bool
	 */
	private bool $is_pill = true;

	/**
	 * Whether to use the value as the indicator label.
	 *
	 * @since $ver$
	 * @var bool
	 */
	private bool $use_value_as_label = false;

	/**
	 * The boolean status mapping.
	 *
	 * @since $ver$
	 * @var array|string[]
	 */
	private array $boolean_statuses = [
		0 => self::STATUS_INACTIVE,
		1 => self::STATUS_ACTIVE,
	];

	/**
	 * The mapping from value to a status type.
	 *
	 * @since $ver$
	 * @var array
	 */
	private array $mapping = [];

	/**
	 * @inheritDoc
	 * @since $ver$
	 * @var string
	 */
	protected string $render = 'datakit_fields.html';

	/**
	 * Returns an instance with a dot.
	 *
	 * @since $ver$
	 *
	 * @return self The field.
	 */
	public function has_dot(): self {
		$clone          = clone $this;
		$clone->has_dot = true;

		return $clone;
	}

	/**
	 * Returns an instance without a dot.
	 *
	 * @since $ver$
	 *
	 * @return self The field.
	 */
	public function has_no_dot(): self {
		$clone          = clone $this;
		$clone->has_dot = false;

		return $clone;
	}

	/**
	 * Returns an instance that is displayed as a pill.
	 *
	 * @since $ver$
	 *
	 * @return self The field.
	 */
	public function pill(): self {
		$clone          = clone $this;
		$clone->is_pill = true;

		return $clone;
	}

	/**
	 * Returns an instance that is displayed as a rectangle.
	 *
	 * @since $ver$
	 *
	 * @return self The field.
	 */
	public function rectangle(): self {
		$clone          = clone $this;
		$clone->is_pill = false;

		return $clone;
	}

	/**
	 * Returns an instance with mapping type.
	 *
	 * @since $ver$
	 *
	 * @param string      $active   The value to use as Active.
	 * @param string|null $inactive The value to use as inactive.
	 * @param string|null $error    The value to use as an error.
	 * @param string|null $warning  The value to use as a warning.
	 * @param string|null $info     The value to use as informative.
	 *
	 * @return self The field.
	 */
	public function mapping(
		string $active,
		?string $inactive = null,
		?string $error = null,
		?string $warning = null,
		?string $info = null
	): self {
		$clone          = clone $this;
		$clone->type    = self::TYPE_MAPPING;
		$clone->mapping = array_flip( array_filter( compact( 'active', 'inactive', 'error', 'warning', 'info' ) ) );

		return $clone;
	}

	/**
	 * Returns an instance with mapping type.
	 *
	 * @since $ver$
	 *
	 * @param string $truthy  The status used for a truthy value.
	 * @param string $falsely The status used for a false value.
	 *
	 * @return self The field.
	 */
	public function boolean( string $truthy = self::STATUS_ACTIVE, string $falsely = self::STATUS_INACTIVE ): self {
		$clone       = clone $this;
		$clone->type = self::TYPE_BOOLEAN;

		$clone->boolean_statuses[1] = $this->validate_status( $truthy );
		$clone->boolean_statuses[0] = $this->validate_status( $falsely );

		return $clone;
	}

	/**
	 * Returns the status type.
	 *
	 * @since $ver$
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return string The status type.
	 */
	private function status( $value ): string {
		if ( self::TYPE_BOOLEAN === $this->type ) {
			return $this->boolean_statuses[ (bool) $value ];
		}

		if ( self::TYPE_MAPPING === $this->type ) {
			return $this->mapping[ $value ] ?? self::STATUS_INACTIVE;
		}

		return self::STATUS_INACTIVE;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function get_value( array $data ) {
		$value   = parent::get_value( $data );
		$classes = [
			sprintf( 'datakit-status-indicator--%s', $this->status( $value ) ),
		];

		$svg = '';

		if ( $this->is_pill ) {
			$classes[] = 'datakit-status-indicator--is-pill';
		}

		if ( $this->has_dot ) {
			$classes[] = 'datakit-status-indicator--has-dot';
			$svg       = '<svg viewBox="0 0 6 6" xmlns="http://www.w3.org/2000/svg"><circle cx="3" cy="2" r="1" stroke-width="2"></circle></svg>';
		}

		$status = $this->get_label( (string) $value );

		return sprintf(
			'<span class="datakit-status-indicator %s">%s<span class="datakit-status-indicator-status">%s</span></span>',
			implode( ' ', $classes ),
			$svg,
			$status,
		);
	}

	/**
	 * Returns the label for the indicator.
	 *
	 * @since $ver$
	 *
	 * @param string $value The value.
	 *
	 * @return string The value.
	 */
	private function get_label( string $value ): string {
		if ( $this->use_value_as_label ) {
			return $value ?: '&nbsp;';
		}

		return $this->labels[ $this->status( $value ) ];
	}

	/**
	 * Validates a provided status.
	 *
	 * @since $ver$
	 *
	 * @param string $status The status.
	 *
	 * @return string The status text.
	 */
	private function validate_status( string $status ): string {
		$statuses = [
			self::STATUS_INACTIVE,
			self::STATUS_ACTIVE,
			self::STATUS_ERROR,
			self::STATUS_WARNING,
			self::STATUS_INFO,
		];

		if ( ! in_array( $status, $statuses, true, ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'A status can only be one of: "%s"; "%s" given. ',
					implode( '", "', $statuses ),
					$status,
				),
			);
		}

		return $status;
	}

	/**
	 * Returns an instance which shows the value as the text.
	 *
	 * @since $ver$
	 */
	public function show_value(): self {
		$clone                     = clone $this;
		$clone->use_value_as_label = true;

		return $clone;
	}

	/**
	 * Returns an instance which shows the label as the text.
	 *
	 * @since $ver$
	 */
	public function show_label(): self {
		$clone                     = clone $this;
		$clone->use_value_as_label = false;

		return $clone;
	}
}
