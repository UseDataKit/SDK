<?php

namespace DataKit\DataViews\Data\Exception;

use DataKit\DataViews\DataViewException;
use DataKit\DataViews\Data\DataSource;
use Throwable;

/**
 * Thrown when the data was not found.
 *
 * @since $ver$
 */
final class DataNotFoundException extends DataViewException {
	/**
	 * The data source that triggered the exception.
	 *
	 * @since $ver$
	 *
	 * @var DataSource
	 */
	private DataSource $data_source;

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function __construct(
		DataSource $data_source,
		$message = 'Dataset for id not found.',
		$code = 404,
		Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );

		$this->data_source = $data_source;
	}

	/**
	 * Creates instance of the exception for a specific ID.
	 *
	 * @since $ver$
	 *
	 * @param DataSource $data_source The data source that triggered the exception.
	 * @param string     $id          The ID of the data set.
	 *
	 * @return self The exception.
	 */
	public static function with_id( DataSource $data_source, string $id ): self {
		return new self( $data_source, sprintf( 'Data set with id "%s" not found.', $id ) );
	}

	/**
	 * Returns the data source that triggered the exception.
	 *
	 * @since $ver$
	 *
	 * @return DataSource
	 */
	public function data_source(): DataSource {
		return $this->data_source;
	}
}
