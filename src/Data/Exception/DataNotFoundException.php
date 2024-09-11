<?php

namespace DataKit\DataViews\Data\Exception;

use DataKit\DataViews\Data\DataSource;
use DataKit\DataViews\Translation\Translator;
use Throwable;

/**
 * Thrown when the data was not found.
 *
 * @since $ver$
 */
final class DataNotFoundException extends DataSourceException {
	/**
	 * The data source that triggered the exception.
	 *
	 * @since $ver$
	 *
	 * @var DataSource
	 */
	private DataSource $data_source;

	/**
	 * The ID of the DataSet.
	 *
	 * @since $ve$
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * @inheritDoc
	 *
	 * @since $ver$
	 */
	public function __construct(
		DataSource $data_source,
		$message = 'datakit.data.not_found',
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
		$exception     = new self( $data_source );
		$exception->id = $id;

		return $exception;
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

	/**
	 * {@inheritDoc}
	 *
	 * @since $ver
	 */
	public function translate( Translator $translator ): string {
		if ( ! isset( $this->id ) ) {
			return parent::translate( $translator );
		}

		return $translator->translate( 'datakit.data.not_found.with_id', [ 'id' => $this->id ] );
	}
}
