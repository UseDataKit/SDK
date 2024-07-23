<?php

namespace DataKit\DataViews\Clock;

use DateTimeInterface;

/**
 * Object that represents a clock.
 *
 * @since $ver$
 */
interface Clock {
	/**
	 * Returns the current time as a DateTimeImmutable object.
	 *
	 * @since $ver$
	 */
	public function now(): DateTimeInterface;
}
