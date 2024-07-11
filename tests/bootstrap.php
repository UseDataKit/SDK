<?php

require 'vendor/autoload.php';

/**
 * Polyfill for apply_filters.
 *
 * @since $ver$
 */
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( ...$args ) {
		return $args[0];
	}
}
