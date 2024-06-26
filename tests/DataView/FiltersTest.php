<?php

namespace DataKit\DataViews\Tests\DataView;

use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Filters;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Filters}
 * @since $ver$
 */
final class FiltersTest extends TestCase {

	/**
	 * Test case for {@see Filters::of()} and {@see FIlters::getIterator()}..
	 * @since $ver$
	 */
	public function test_create_and_iterate() : void {
		$filters = Filters::of(
			Filter::is( 'id', 2 ),
			Filter::isNot( 'name', 'Doeke' ),
		);

		foreach ( $filters as $filter ) {
			self::assertInstanceOf( Filter::class, $filter );
		}
	}

	/**
	 * Test case for {@see Filters::from_array()} and {@see Filters::to_array()}.
	 * @since $ver$
	 */
	public function test_array_serialization() : void {
		$filters   = Filters::of(
			Filter::is( 'id', 2 ),
			Filter::isNot( 'name', 'Doeke' ),
		);

		$array     = $filters->to_array();
		$filters_2 = Filters::from_array( $array );

		self::assertEquals( $filters, $filters_2 );
	}
}
