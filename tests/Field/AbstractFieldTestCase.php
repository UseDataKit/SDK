<?php

namespace DataKit\DataViews\Tests\Field;

use DataKit\DataViews\Field\Field;
use PHPUnit\Framework\TestCase;

/**
 * Abstract test case for all Fields.
 *
 * This class contains tests that should pass for all field types.
 *
 * @since $ver$
 *
 * @template T of Field The field type.
 */
abstract class AbstractFieldTestCase extends TestCase {
	/**
	 * Should return the field class name that is being tested.
	 *
	 * @since $ver$
	 * @return string
	 */
	abstract protected static function fieldClass(): string;

	/**
	 * Creates the field using the `::create` method.
	 *
	 * @since $ver$
	 *
	 * @param string $id     The field id.
	 * @param string $header the field header.
	 *
	 * @return T The field.
	 */
	protected function createField( string $id, string $header ): Field {
		$field_class = static::fieldClass();

		return call_user_func( [ $field_class, 'create' ], $id, $header );
	}

	/**
	 * Test case for {@see Field::create()}.
	 *
	 * @since $ver$
	 */
	public function testCreate(): void {
		$field = $this->createField( 'field_id', 'Field header' );

		self::assertSame( 'field_id', $field->id() );
		self::assertSame( 'Field header', $field->label() );
	}

	/**
	 * Test case for {@see Field::to_array()}.
	 *
	 * @since $ver$
	 * @return T The field.
	 */
	public function testToArray(): Field {
		$field       = $this->createField( 'field_id', 'Field header' );
		$field_array = $field->to_array();

		self::assertSame( $field->uuid(), $field_array['id'] );
		self::assertSame( 'Field header', $field_array['label'] );
		self::assertTrue( $field_array['enableHiding'] );
		self::assertTrue( $field_array['enableSorting'] );

		return $field;
	}

	/**
	 * Testcase for different field modifiers.
	 *
	 * @since $ver$
	 */
	public function testModifier(): void {
		$field        = $this->createField( 'field_id', 'Field header' );
		$not_hideable = $field->always_visible();
		$hideable     = $not_hideable->hideable();
		$not_sortable = $hideable->not_sortable();
		$sortable     = $not_sortable->sortable();

		$badge  = $field->badge();
		$column = $badge->column();
		$row    = $badge->row();

		self::assertFalse( $field->is_badge() );
		self::assertTrue( $badge->is_badge() );
		self::assertFalse( $column->is_badge() );
		self::assertTrue( $column->is_column() );
		self::assertFalse( $row->is_column() );

		self::assertTrue( $field->to_array()['enableHiding'] );
		self::assertTrue( $hideable->to_array()['enableHiding'] );
		self::assertTrue( $sortable->to_array()['enableSorting'] );
		self::assertFalse( $not_hideable->to_array()['enableHiding'] );
		self::assertFalse( $not_sortable->to_array()['enableSorting'] );
	}

	/**
	 * Test case for {@see Field::callback()}.
	 *
	 * @since $ver$
	 */
	public function testCallback(): void {
		$field = $this->createField( 'email', 'Email Address' )
			->callback( function ( string $id, array $data ): string {
				$value = $data[ $id ] ?? '';
				if ( strlen( $value ) <= 15 ) {
					return $value;
				}

				// Truncate any value longer than 20 characters.
				return substr( $value, 0, 15 ) . '...';
			} );

		$result = $field->get_value( [ 'email' => 'person@gravitykit.com', 'name' => 'Doeke Norg' ] );
		self::assertStringContainsString( 'person@gravityk...', $result );
	}
}
