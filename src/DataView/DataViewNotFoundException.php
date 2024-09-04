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
	 * Create the exception.
	 *
	 * @since $ver$
	 *
	 * @param string         $message  The message.
	 * @param Exception|null $previous The previous exception.
	 */
	public function __construct( $message = 'datakit.dataview.not_found', Exception $previous = null ) {
		parent::__construct( $message, 404, $previous );
	}
}
