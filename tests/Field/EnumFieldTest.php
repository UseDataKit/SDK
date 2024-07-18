<?php

namespace DataKit\DataViews\Tests\Field;

use DataKit\DataViews\DataView\Operator;
use DataKit\DataViews\Field\EnumField;
use DataKit\DataViews\Field\Field;
use PHPUnit\Framework\Exception;

/**
 * Unit tests for {@see EnumField}.
 *
 * @since $ver$
 */
final class EnumFieldTest extends AbstractFieldTestCase {
	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected static function fieldClass() : string {
		return EnumField::class;
	}

	/**
	 * @inheritDoc
	 * @since $ver$
	 */
	protected function createField( string $id, string $header ) : Field {
		$field_class = static::fieldClass();

		return call_user_func( [ $field_class, 'create' ], $id, $header, [
			'active'   => 'Active',
			'disabled' => 'Inactive',
		] );
	}

	/**
	 * @inheritDoc
	 *
	 * Adds specific tests for EnumField.
	 *
	 * @since $ver$
	 */
	public function testToArray() : Field {
		$field       = parent::testToArray();
		$field_array = $field->to_array();

		self::assertNull( $field_array['filterBy'] );
		self::assertSame(
			[
				[ 'label' => 'Active', 'value' => 'active' ],
				[ 'label' => 'Inactive', 'value' => 'disabled' ],
			],
			$field_array['elements'],
		);

		if ( ! $field instanceof EnumField ) {
			throw new Exception( 'Field instance should be an enum.' );
		}

		$with_operators  = $field->filterable_by( Operator::isAny(), Operator::isNone() );
		$operators_array = $with_operators->to_array();

		self::assertSame( [ 'isAny', 'isNone' ], $operators_array['filterBy']['operators'] );
		self::assertFalse( $operators_array['filterBy']['isPrimary'] );

		$primary = $with_operators->primary();
		self::assertTrue( $primary->to_array()['filterBy']['isPrimary'] );
		$secondary = $primary->secondary();
		self::assertFalse( $secondary->to_array()['filterBy']['isPrimary'] );

		return $field;
	}
}
