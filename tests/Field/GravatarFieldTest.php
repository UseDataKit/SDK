<?php

namespace DataKit\DataViews\Tests\Field;

use DataKit\DataViews\Field\GravatarField;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see GravatarField}
 *
 * @since $ver$
 */
final class GravatarFieldTest extends TestCase {
	/**
	 * Test case for {@see GravatarField::get_value()}
	 *
	 * @since $ver$
	 */
	public function test_get_value(): void {
		$field = GravatarField::create( 'email', 'Picture' )
			->rating( 'pg' )
			->resolution( 200 )
			->default_image( 'wavatar' )
			->size( 80, 80 )
			->alt( 'Alt for {email}' );

		$html = $field->get_value( [ 'email' => 'doeke@datakit.org' ] );
		self::assertStringContainsString( '<img', $html );
		self::assertStringContainsString( 'width="80" height="80"', $html );
		self::assertStringContainsString( 'alt="Alt for doeke@datakit.org"', $html );
		self::assertStringContainsString(
			'src="https://gravatar.com/avatar/' . md5( 'doeke@datakit.org' ) . '?size=200&default=wavatar&rating=pg',
			$html,
		);
	}
}
