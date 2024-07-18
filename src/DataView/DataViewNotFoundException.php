<?php

namespace DataKit\DataViews\DataView;

use DataKit\DataViews\DataViewException;
use Exception;

/**
 * Exception thrown when a DataView was not found.
 *
 * @since $ver$
 */
final class DataViewNotFoundException extends DataViewException {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	public function __construct( $message = "The DataView was not found.", Exception $previous = null ) {
		parent::__construct( $message, 404, $previous );
	}
}
