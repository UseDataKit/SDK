<?php

namespace DataKit\DataViews\Tests\DataView;

use BadMethodCallException;
use DataKit\DataViews\DataView\Filter;
use DataKit\DataViews\DataView\Operator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see Filter}
 * @since $ver$
 */
final class FilterTest extends TestCase {
	/**
	 * Data Provider for {@see FilterTest::test_constructors}.
	 * @since $ver$
	 * @return array[]
	 */
	public static function constructors_provider() : array {
		return [
			'is'       => [ Operator::is(), 'value' ],
			'isNot'    => [ Operator::isNot(), 'value' ],
			'isNone'   => [ Operator::isNone(), [ 'value', 'another' ] ],
			'isAny'    => [ Operator::isAny(), [ 'value', 'another' ] ],
			'isAll'    => [ Operator::isAll(), [ 'value', 'another' ] ],
			'isNotAll' => [ Operator::isNotAll(), [ 'value', 'another' ] ],
		];
	}

	/**
	 * Data Provider for {@see FilterTest::test_invalid_constructors}.
	 * @since $ver$
	 * @return array[]
	 */
	public static function invalid_constructors_provider() : array {
		return [
			'is'       => [ Operator::is(), [ 'value' ] ],
			'isNot'    => [ Operator::isNot(), [ 'value' ] ],
			'isNone'   => [ Operator::isNone(), 'value' ],
			'isAny'    => [ Operator::isAny(), 'value' ],
			'isAll'    => [ Operator::isAll(), 'value' ],
			'isNotAll' => [ Operator::isNotAll(), 'value' ],
		];
	}

	/**
	 * Data Provider for {@see FilterTest::test_from_array_invalid()}.
	 * @since $ver$
	 * @return array[]
	 */
	public static function invalid_array_provider() : array {
		return [
			'invalid operator' => [
				[ 'field' => 'field', 'operator' => 'invalid', 'value' => 'value' ],
				InvalidArgumentException::class,
			],
			'missing field'    => [
				[ 'operator' => 'is', 'value' => 'value' ],
				InvalidArgumentException::class,
			],
		];
	}

	/**
	 * Data Provider for {@see FilterTest::test_matches()}.
	 * @since $ver$
	 * @return array[]
	 */
	public static function match_provider() : array {
		return [
			'is true'        => [ Filter::is( 'id', 2 ), true ],
			'is false'       => [ Filter::is( 'id', 3 ), false ],
			'isnot true'     => [ Filter::isNot( 'id', 3 ), true ],
			'isnot false'    => [ Filter::isNot( 'id', 2 ), false ],
			'isAny true'     => [ Filter::isAny( 'name', [ 'Doeke', 'Zack' ] ), true ],
			'isAny false'    => [ Filter::isAny( 'name', [ 'Vlad', 'Zack' ] ), false ],
			'isAll true 1'   => [ Filter::isAll( 'selected', [ 'one' ] ), true ],
			'isAll true 2'   => [ Filter::isAll( 'selected', [ 'one', 'two' ] ), true ],
			'isAll false'    => [ Filter::isAll( 'selected', [ 'one', 'two', 'three' ] ), false ],
			'isNotAll true'  => [ Filter::isNotAll( 'selected', [ 'one', 'two', 'three' ] ), true ],
			'isNotAll false' => [ Filter::isNotAll( 'selected', [ 'one', 'two' ] ), false ],
			'isNone true'    => [ Filter::isNone( 'selected', [ 'random' ] ), true ],
			'isNone false'   => [ Filter::isNone( 'selected', [ 'one', 'two' ] ), false ],
			'field missing'  => [ Filter::is( 'missing', 'value' ), false ],
		];
	}

	/**
	 * Data Provider for {@see FilterTest::test_missing_arguments_constructor()}.
	 * @since $ver$
	 * @return array[]
	 */
	public static function missing_arguments_provider() : array {
		return [
			'no arguments'  => [ [], 'Method "is" expects exactly 2 arguments, 0 given.' ],
			'missing value' => [ [ 'field' ], 'Method "is" expects exactly 2 arguments, 1 given.' ],
		];
	}

	/**
	 * Test case for {@see Filter::__callStatic()}.
	 * @since $ver$
	 * @dataProvider constructors_provider
	 */
	public function test_constructors( Operator $operator, $value ) : void {
		$method = (string) $operator;
		$filter = Filter::$method( 'field', $value );
		$array  = $filter->to_array();

		self::assertSame( [
			'field'    => 'field',
			'operator' => (string) $operator,
			'value'    => $value,
		], $array );
	}

	/**
	 * Test case for {@see Filter::__callStatic()} with invalid arguments.
	 * @since $ver$
	 * @dataProvider invalid_constructors_provider
	 */
	public function test_invalid_constructors( Operator $operator, $value ) : void {
		$this->expectException( BadMethodCallException::class );

		$method = (string) $operator;
		Filter::$method( 'field', $value );
	}

	/**
	 * Test case for {@see Filter::__callStatic()} with invalid operator.
	 * @since $ver$
	 */
	public function test_invalid_operator_constructor() : void {
		$this->expectException( 'BadMethodCallException' );
		$this->expectExceptionMessage( 'Static method "invalid" not found.' );

		// @phpstan-ignore staticMethod.notFound
		Filter::invalid( 'field', 'value' );
	}

	/**
	 * Test case for {@see Filter::__callStatic()} with missing arguments.
	 * @since $ver$
	 * @dataProvider missing_arguments_provider
	 */
	public function test_missing_arguments_constructor( array $arguments, string $expected_message ) : void {
		$this->expectException( 'BadMethodCallException' );
		$this->expectExceptionMessage( $expected_message );

		Filter::is( ...$arguments );
	}

	/**
	 * Test case for {@see Filter::matches()}.
	 * @since $ver$
	 * @dataProvider match_provider
	 */
	public function test_matches( Filter $filter, bool $expected_result ) : void {
		self::assertSame(
			$expected_result,
			$filter->matches( [
				'id'       => 2,
				'name'     => 'Doeke',
				'selected' => [ 'one', 'two' ],
			] ),
		);
	}

	/**
	 * Test case for {@see Filter::from_array()}.
	 * @since $ver$
	 */
	public function test_from_array() : void {
		$filter = Filter::from_array( $array = [
			'field'    => 'test-field',
			'operator' => 'isNot',
			'value'    => 'test-value',
		] );

		self::assertSame( $array, $filter->to_array() );
	}

	/**
	 * Test case for {@see Filter::from_array()} with invalid values.
	 * @since $ver$
	 * @dataProvider invalid_array_provider
	 */
	public function test_from_array_invalid( array $invalid_array, string $expected_exception ) : void {
		$this->expectException( $expected_exception );
		Filter::from_array( $invalid_array );
	}


	/**
	 * Test case for
	 * @since $ver$
	 */
	public function test_flatt_value() : void {
		$filter = Filter::is( 'field',
			new class {
				public function __toString() : string {
					return 'value';
				}
			} );

		self::assertSame(
			[ 'field' => 'field', 'operator' => 'is', 'value' => 'value' ],
			$filter->to_array(),
		);
	}
}
