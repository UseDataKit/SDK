<?php

namespace DataKit\DataViews\Data\Exception;

use DataKit\DataViews\DataViewsException;

/**
 * Thrown when the data source could not be found.
 */
final class DataSourceNotFoundException extends DataViewsException {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function __construct( $message = "Data source not found.", $code = 404, \Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}
}
