<?php

namespace DataKit\DataViews\Data\Exception;

use Throwable;

/**
 * Thrown when the data source could not be found.
 *
 * @since $ver$
 */
final class DataSourceNotFoundException extends DataSourceException {
	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 *
	 * @phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
	 */
	public function __construct( $message = 'datakit.data_source.not_found', $code = 404, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}
