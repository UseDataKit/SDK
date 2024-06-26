<?php

namespace DataKit\DataViews\DataView;

use DataKit\DataViews\DataViewsException;
use Exception;

/**
 * Exception thrown when a DataView was not found.
 * @since $ver$
 */
final class DataViewsNotFoundException extends DataViewsException {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function __construct( $message = "The DataView was not found.", Exception $previous = null ) {
		parent::__construct( $message, 404, $previous );
	}
}
