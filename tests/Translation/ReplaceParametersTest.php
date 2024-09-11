<?php

namespace DataKit\DataViews\Tests\Translation;

use DataKit\DataViews\Translation\ReplaceParameters;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for {@see ReplaceParameters}
 *
 * @since $ver$
 */
final class ReplaceParametersTest extends TestCase {
	/**
	 * Test case for
	 *
	 * @since $ver$
	 */
	public function test_replace_parameters(): void {
		$replacer = new class {
			use ReplaceParameters {
				replace_parameters as public;
			}
		};

		self::assertSame(
			'ID: 123 and name: Person',
			$replacer->replace_parameters( 'ID: [id] and name: [name]', [ 'id' => '123', 'name' => 'Person' ] )
		);
	}
}
