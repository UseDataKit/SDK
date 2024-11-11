<?php

require dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( ...$args ) {
		return $args[0];
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( ...$args ) {
		return $args[0];
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( ...$args ) {
		return $args[0];
	}
}
