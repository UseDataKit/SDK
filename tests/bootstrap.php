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

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( ...$args ) {
		return $args[0];
	}
}
