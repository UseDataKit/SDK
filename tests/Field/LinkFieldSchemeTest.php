<?php

namespace DataKit\DataViews\Tests\Field;

use DataKit\DataViews\Field\LinkField;
use PHPUnit\Framework\TestCase;

/**
 * A link's href comes from row data, so its scheme is attacker-controlled.
 *
 * `LinkField` escaped the href with `esc_attr()`, which makes a value safe to
 * sit inside a quoted attribute and says nothing about what the attribute
 * means. `javascript:alert(1)` contains no character `esc_attr()` touches, so
 * a URL submitted through a form and rendered in a dashboard was stored XSS.
 *
 * The escaping was never missing, which is why it reads as handled. Escaping
 * and scheme filtering are different jobs.
 */
final class LinkFieldSchemeTest extends TestCase {

	private function render( string $href ): string {
		return LinkField::create( 'link', 'Link' )->get_value( [ 'link' => $href ] );
	}

	/**
	 * @dataProvider dangerousHrefs
	 */
	public function test_a_dangerous_scheme_does_not_reach_the_href( string $href ): void {
		self::assertStringNotContainsString( 'javascript', strtolower( $this->render( $href ) ) );
		self::assertStringNotContainsString( 'vbscript', strtolower( $this->render( $href ) ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function dangerousHrefs(): array {
		return [
			'javascript' => [ 'javascript:alert(1)' ],
			'uppercase' => [ 'JavaScript:alert(1)' ],
			'mixed with spaces' => [ '  javascript:alert(1)' ],
			// A tab inside the scheme is ignored by browsers when they parse
			// the URL, so stripping only leading whitespace is not enough.
			'tab inside scheme' => [ "java\tscript:alert(1)" ],
			'newline inside scheme' => [ "java\nscript:alert(1)" ],
			'vbscript' => [ 'vbscript:msgbox(1)' ],
		];
	}

	public function test_a_data_uri_is_refused(): void {
		self::assertStringNotContainsString( 'data:', $this->render( 'data:text/html,<script>alert(1)</script>' ) );
	}

	/**
	 * Positive controls. Without these the assertions above would hold for a
	 * field that rendered nothing at all.
	 *
	 * @dataProvider safeHrefs
	 */
	public function test_an_ordinary_url_still_renders( string $href ): void {
		self::assertStringContainsString( 'href="' . $href . '"', $this->render( $href ) );
	}

	/**
	 * @return array<string, array{string}>
	 */
	public static function safeHrefs(): array {
		return [
			'https' => [ 'https://datakit.org/a?b=c&d=e' ],
			'http' => [ 'http://example.test/path' ],
			'mailto' => [ 'mailto:someone@example.test' ],
			'tel' => [ 'tel:+15555550123' ],
			'relative' => [ '/wp-admin/admin.php?page=datakit' ],
			'relative no slash' => [ 'reports/weekly' ],
		];
	}

	/**
	 * A refused href renders nothing rather than an anchor to "".
	 */
	public function test_a_refused_href_renders_no_anchor(): void {
		self::assertSame( '', $this->render( 'javascript:alert(1)' ) );
	}
}
