<?php

namespace DataKit\DataViews\Tests\Data;

use DataKit\DataViews\Data\BaseDataSource;
use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Filters;
use DataKit\DataViews\DataView\Sort;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see BaseDataSource}
 *
 * @since $ver$
 */
final class BaseDataSourceTest extends TestCase {
	/**
	 * The data source used for testing.
	 *
	 * @since $ver$
	 * @var BaseDataSource
	 */
	private BaseDataSource $data_source;

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->data_source = new class extends BaseDataSource {
			// upgrade visibility for test.
			public ?Sort    $sort    = null;

			public ?Filters $filters = null;

			public function id(): string {
				return 'test';
			}

			public function name(): string {
				return 'Test';
			}

			public function get_data_ids( int $limit = 100, int $offset = 0 ): array {
				return [];
			}

			public function get_data_by_id( string $id ): array {
				return [];
			}

			public function count(): int {
				return 0;
			}

			public function get_fields(): array {
				return [];
			}
		};
	}

	/**
	 * Test case for {@see BaseDataSource::sort_by()}
	 *
	 * @since $ver$
	 */
	public function test_sort_by(): void {
		$sort    = Sort::asc( 'some-field' );
		$source  = $this->data_source->sort_by( $sort );
		$removed = $source->sort_by( null );

		self::assertNotSame( $source, $this->data_source );
		self::assertNotSame( $source, $removed );
		// @phpstan-ignore property.protected
		self::assertSame( $source->sort, $sort );
		// @phpstan-ignore property.protected
		self::assertNull( $removed->sort );
		// @phpstan-ignore property.protected
		self::assertNull( $this->data_source->sort );
	}

	/**
	 * Test case for {@see BaseDataSource::filter_by()}
	 *
	 * @since $ver$
	 */
	public function test_filter_by(): void {
		$filters = Filters::of(
			Filter::is( 'field', 'value' ),
		);
		$source  = $this->data_source->filter_by( $filters );
		$removed = $source->filter_by( null );

		self::assertNotSame( $source, $this->data_source );
		self::assertNotSame( $source, $removed );
		// @phpstan-ignore property.protected
		self::assertSame( $source->filters, $filters );
		// @phpstan-ignore property.protected
		self::assertNull( $removed->filters );
		// @phpstan-ignore property.protected
		self::assertNull( $this->data_source->filters );
	}
}
