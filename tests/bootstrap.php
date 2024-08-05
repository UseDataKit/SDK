<?php

require dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( ...$args ) {
		return $args[0];
	}
}
