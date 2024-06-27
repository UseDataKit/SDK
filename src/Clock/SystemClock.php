<?php

namespace DataKit\DataViews\Clock;

/**
 * Clock that uses the system time.
 * @since $ver$
 */
final class SystemClock implements Clock {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function now() : \DateTimeImmutable {
		return new \DateTimeImmutable();
	}
}
