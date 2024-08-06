<?php

namespace DataKit\DataViews\Tests\Data\DataMatcher;

use DataKit\DataViews\Data\DataMatcher\ArrayDataMatcher;
use DataKit\DataViews\DataView\Search;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ArrayDataMatcher}.
 *
 * @since $ver$
 */
final class ArrayDataMatcherTest extends TestCase {
	/**
	 * Test case for {@see ArrayDataMatcher::is_data_matched_by_string()}.
	 *
	 * @since $ver$
	 */
	public function test_is_data_matched_by_string(): void {
		$data = [
			'id'    => 1,
			'name'  => 'Person Name',
			'email' => 'person@business.test',
		];

		self::assertFalse( ArrayDataMatcher::is_data_matched_by_string( $data, 'missing' ) );
		self::assertTrue( ArrayDataMatcher::is_data_matched_by_string( $data, 'person' ) );
		self::assertTrue( ArrayDataMatcher::is_data_matched_by_string( $data, 'name business' ) );
		self::assertFalse( ArrayDataMatcher::is_data_matched_by_string( $data, 'name person', true ) ); // Exact match.
	}


	/**
	 * Data provider for {@see self::test_is_data_matched_by_search()}.
	 *
	 * @since $ver$
	 * @return array[] The test data.
	 */
	public static function dataprovider_for_search_match(): array {
		// Note: every "name person" test is a check if the `exact match` is on. Otherwise, it will
		// split the multiple words into separate check; which *will* pass.

		return [
			'empty'                                => [ '', true ],
			'optional missing'                     => [ 'missing', false ],
			'optional missing group'               => [ '"name person"', false ],
			'optional match'                       => [ 'person', true ],
			'optional multiple match'              => [ 'name person', true ],
			'required match'                       => [ '+person', true ],
			'required multiple match'              => [ '+person +business +"person name"', true ],
			'required missing'                     => [ '+"name person"', false ],
			'ignored missing'                      => [ '-missing', true ],
			'ignored multiple missing'             => [ '-missing -missing2 -"name person"', true ],
			'ignored match'                        => [ '-person', false ],
			'required missing with optional match' => [ '+missing person', false ],
			'required match with optional missing' => [ '+person missing', true ],
			'required match with required missing' => [ '+person +missing', false ],
			'ignored missing with optional match'  => [ '-missing person', true ],
			'ignored match with optional match'    => [ '-person business', false ],
			'ignored missing with required match'  => [ '-missing +business', true ],
			'optional strict search'               => [ '"-negative" "+positive"', true ],
			'ignored strict search'                => [ '-"-negative"', false ],
			'ignored strict search 2'              => [ '-"+positive"', false ],
			'required strict search'               => [ '+"-negative" +"+positive"', true ],
		];
	}

	/**
	 * Test case for {@see ArrayDataMatcher::is_data_matched_by_search()}.
	 *
	 * @since        $ver$
	 * @dataProvider dataprovider_for_search_match The data provider.
	 */
	public function test_is_data_matched_by_search( string $query, bool $expected_result ): void {
		$data = [
			'id'    => 1,
			'name'  => 'Person Name',
			'email' => 'person@business.test',
			'other' => '-negative +positive',
		];

		self::assertSame(
			$expected_result,
			ArrayDataMatcher::is_data_matched_by_search( $data, Search::from_string( $query ) ),
		);
	}
}
