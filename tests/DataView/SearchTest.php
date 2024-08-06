<?php

namespace DataKit\DataViews\Tests\DataView;

use DataKit\DataViews\DataView\Search;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Search}
 *
 * @since $ver$
 */
final class SearchTest extends TestCase {
	/**
	 * Dataprovider used by {@see self::test_parse()}.
	 *
	 * @since $ver$
	 * @return array<string, array> The test data.
	 */
	public static function dataprovider_parse(): array {
		return [
			'empty'    => [
				'',
				[
					'required' => [],
					'optional' => [],
					'ignored'  => [],
				],
			],
			'optional' => [
				'every word "or group" is optional',
				[
					'required' => [],
					'optional' => [ 'every', 'word', 'or group', 'is', 'optional' ],
					'ignored'  => [],
				],
			],
			'ignored'  => [
				'-ignore -"us all"',
				[
					'required' => [],
					'optional' => [],
					'ignored'  => [ 'ignore', 'us all' ],
				],
			],
			'required' => [
				'+this +"and all that"',
				[
					'required' => [ 'this', 'and all that' ],
					'optional' => [],
					'ignored'  => [],
				],
			],
			'combined' => [
				'some +required "value " -other "Exactly this" +"required group" -"ignored  group"',
				[
					'required' => [ 'required', 'required group' ],
					'optional' => [ 'some', 'value ', 'Exactly this' ],
					'ignored'  => [ 'other', 'ignored  group' ],
				],
			],
		];
	}

	/**
	 * Test case for {@see Search::parse()}.
	 *
	 * @since        $ver$
	 * @dataProvider dataprovider_parse The dataprovider.
	 */
	public function test_parse( string $query, array $expected_result ): void {
		$search = Search::from_string( $query );
		self::assertSame( $expected_result, $search->to_array() );
		self::assertSame( $query, (string) $search );
	}

	/**
	 * Test case for {@see Search::optional()}, {@see Search::required()} and {@see Search::ignored()}.
	 *
	 * @since $ver$
	 */
	public function test_getters(): void {
		$search = Search::from_string( 'optional "optional group" +required +"required group" -ignored -"ignored group"' );

		self::assertSame( [ 'optional', 'optional group' ], $search->optional() );
		self::assertSame( [ 'required', 'required group' ], $search->required() );
		self::assertSame( [ 'ignored', 'ignored group' ], $search->ignored() );
	}

	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_as_string(): void {
		$no_parsing = Search::from_string( ' +this +"and all" that ' )->without_parsing();
		self::assertSame(
			[
				'required' => [],
				'optional' => [ '+this', '+"and', 'all"', 'that' ],
				'ignored'  => [],
			],
			$no_parsing->to_array(),
		);

		$parsing = $no_parsing->with_parsing();
		self::assertSame(
			[
				'required' => [ 'this', 'and all' ],
				'optional' => [ 'that' ],
				'ignored'  => [],
			],
			$parsing->to_array(),
		);
	}
}
