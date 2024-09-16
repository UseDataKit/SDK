<?php

namespace DataKit\DataViews\Tests;

use DataKit\DataViews\EnumObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see EnumObject}
 *
 * @since $ver$
 */
final class EnumObjectTest extends TestCase {
	/**
	 * Test case for {@see EnumObject::equals()}.
	 *
	 * @since $ver$
	 */
	public function test_api(): void {
		$enum_1    = TestEnum::One();
		$enum_1_2  = TestEnum::One();
		$enum_2    = TestEnum::Two();
		$another_1 = TestAnotherEnum::One();

		self::assertTrue( $enum_1->equals( $enum_1_2 ) );
		self::assertFalse( $enum_2->equals( $enum_1 ) );
		self::assertFalse( $enum_1->equals( $another_1 ) ); // @phpstan-ignore argument.type

		self::assertSame( 'value one', $enum_1->as_string() );
		self::assertSame( 'value one', (string) $enum_1 );
	}

	/**
	 * Test case for {@see EnumObject::__callStatic}.
	 *
	 * @since $ver$
	 */
	public function test_construction(): void {
		$this->expectException( \InvalidArgumentException::class );
		TestEnum::invalid(); // @phpstan-ignore staticMethod.notFound
	}
}

/**
 * Used for test purposes only.
 *
 * @since $ver$
 * @method static self One()
 * @method static self Two()
 */
final class TestEnum extends EnumObject {
	protected static function cases(): array {
		return [
			'One' => 'value one',
			'Two' => 'value two',
		];
	}
}

/**
 * Used for test purposes only.
 *
 * @since $ver$
 * @method static self One()
 */
final class TestAnotherEnum extends EnumObject {
	protected static function cases(): array {
		return [
			'One' => 'value one',
		];
	}
}
