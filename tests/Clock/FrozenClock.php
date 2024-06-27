<?php

namespace DataKit\DataViews\Tests\Clock;

use DataKit\DataViews\Clock\Clock;
use DateTimeImmutable;

/**
 * A frozen clock instance for testing purposes.
 * @since 2.0.0
 */
final class FrozenClock implements Clock {
	/**
	 * The current datetime object.
	 * @since 2.0.0
	 * @var DateTimeImmutable
	 */
	private DateTimeImmutable $time;

	/**
	 * Creates a frozen clock.
	 * @since $ver$
	 *
	 * @param string $datetime The date time to instantiate the clock with.
	 */
	public function __construct( string $datetime ) {
		$this->travel_to( $datetime );
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function now() : DateTimeImmutable {
		return $this->time;
	}

	/**
	 * Changes the time to a new datetime.
	 *
	 * @since $ver$
	 *
	 * @param string $datetime The new datetime.
	 * @param string $format The format the datetime is in.
	 */
	public function travel_to( string $datetime, string $format = 'Y-m-d H:i:s' ) : void {
		$time = DateTimeImmutable::createFromFormat( $format, $datetime );

		$this->time = $time;
	}
}
