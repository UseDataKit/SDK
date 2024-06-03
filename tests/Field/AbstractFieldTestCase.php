<?php

namespace DataKit\DataView\Tests\Field;

use DataKit\DataView\Field\Field;
use PHPUnit\Framework\TestCase;

/**
 * Abstract test case for all Fields.
 *
 * This class contains tests that should pass for all field types.
 *
 * @since $ver$
 */
abstract class AbstractFieldTestCase extends TestCase {
	/**
	 * Should return the field class name that is being tested.
	 * @since $ver$
	 * @return string
	 */
	abstract protected static function fieldClass() : string;

	/**
	 * Creates the field using the `::create` method.
	 * @since $ver$
	 *
	 * @param string $id The field id.
	 * @param string $header the field header.
	 *
	 * @return Field The field instance.
	 */
	protected function createField( string $id, string $header ) : Field {
		$field_class = static::fieldClass();
		$field       = call_user_func( [ $field_class, 'create' ], $id, $header );

		return $field;
	}

	/**
	 * Test case for {@see Field::create()}.
	 * @since $ver$
	 */
	public function testCreate() : void {
		$field = $this->createField( 'field_id', 'Field header' );

		self::assertSame( 'field_id', $field->id() );
		self::assertSame( 'Field header', $field->header() );
	}

	/**
	 * Test case for {@see Field::toArray()}.
	 * @since $ver$
	 */
	public function testToArray() : void {
		$field       = $this->createField( 'field_id', 'Field header' );
		$field_array = $field->toArray();

		self::assertSame( 'field_id', $field_array['id'] );
		self::assertSame( 'Field header', $field_array['header'] );
		self::assertTrue( $field_array['enableHiding'] );
		self::assertTrue( $field_array['enableSorting'] );
		self::assertNull( $field_array['filterBy'] );
	}

	public function testModifier() : void {
		$field        = $this->createField( 'field_id', 'Field header' );
		$not_hideable = $field->not_hideable();
		$hideable     = $not_hideable->hideable();
		$not_sortable = $hideable->not_sortable();
		$sortable     = $not_sortable->sortable();

		self::assertTrue( $field->toArray()['enableHiding'] );
		self::assertTrue( $hideable->toArray()['enableHiding'] );
		self::assertTrue( $sortable->toArray()['enableSorting'] );
		self::assertFalse( $not_hideable->toArray()['enableHiding'] );
		self::assertFalse( $not_sortable->toArray()['enableSorting'] );
	}
}
