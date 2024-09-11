<?php

namespace DataKit\DataViews;

use DataKit\DataViews\Translation\Translatable;
use DataKit\DataViews\Translation\Translator;
use Exception;

/**
 * Exception from the DataView namespace.
 *
 * @since $ver$
 */
class DataViewException extends Exception implements Translatable {
	/**
	 * {@inheritDoc}
	 *
	 * @since $ver$
	 */
	public function translate( Translator $translator ): string {
		return $translator->translate( $this->getMessage() );
	}
}
