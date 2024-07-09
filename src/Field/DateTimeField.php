<?php

namespace DataKit\DataViews\Field;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Represents a field that renders a date and time.
 *
 * @since $ver$
 */
final class DateTimeField extends Field {
	/**
	 * The format the datetime is in.
	 *
	 * @since $ver$
	 * @var string|null
	 */
	private ?string $from_format = null;

	/**
	 * The format to represent the datetime in.
	 *
	 * @since $ver$
	 * @var string
	 */
	private string $to_format = 'Y-m-d H:i:s';

	/**
	 * The timezone the original datetime is in.
	 *
	 * @since $ver$
	 * @var DateTimeZone|null
	 */
	private ?DateTimeZone $from_timezone = null;

	/**
	 * The time zone the datetime should contain.
	 *
	 * @since $ver$
	 * @var DateTimeZone|null
	 */
	private ?DateTimeZone $to_timezone = null;

	/**
	 * @inheritDoc
	 * @since $ver
	 */
	protected string $render = 'datakit_fields.html';


	/**
	 * Applies a format to the date.
	 *
	 * @since $ver$
	 *
	 * @param string            $format      The format to use.
	 * @param DateTimeZone|null $to_timezone The timezone to format the datetime in.
	 *
	 * @return self A date time field.
	 */
	public function to_format( string $format, ?DateTimeZone $to_timezone = null ) : self {
		$clone              = clone $this;
		$clone->to_format   = $format;
		$clone->to_timezone = $to_timezone;

		return $clone;
	}

	/**
	 * Applies the format used to read the original date,
	 *
	 * @since $ver$
	 *
	 * @param string|null       $format    The format to apply.
	 * @param DateTimeZone|null $time_zone The timezone the datetime is in.
	 *
	 * @return self A datetime field.
	 */
	public function from_format( ?string $format, ?DateTimeZone $time_zone = null ) : self {
		$clone                = clone $this;
		$clone->from_format   = $format;
		$clone->from_timezone = $format;

		return $clone;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 * @return string
	 */
	public function get_value( array $data ) : string {
		$value = parent::get_value( $data );

		try {
			$datetime = $this->from_format
				? DateTimeImmutable::createFromFormat( $this->from_format ?? '', $value, $this->from_timezone )
				: new DateTimeImmutable( $value, $this->from_timezone );
		} catch ( Exception $e ) {
			$datetime = false;
		}

		if ( $datetime === false ) {
			return $value;
		}

		if ( $this->to_timezone ) {
			$datetime = $datetime->setTimezone( $this->to_timezone );
		}

		return $datetime->format( $this->to_format );
	}
}
