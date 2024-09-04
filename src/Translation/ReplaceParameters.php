<?php

namespace DataKit\DataViews\Translation;

/**
 * Helper trait to replace parameters on a string.
 *
 * @since $ver$
 */
trait ReplaceParameters {
	/**
	 * Replaces any parameters found in square brackets with the value.
	 *
	 * @since $ver$
	 *
	 * @param string $message    The message.
	 * @param array  $parameters The parameters to replace.
	 *
	 * @return string The message with replaced parameters.
	 */
	protected function replace_parameters( string $message, array $parameters = [] ): string {
		$values = array_values( $parameters );
		$keys   = array_map( static fn( string $key ): string => '[' . $key . ']', array_keys( $parameters ) );

		return strtr( $message, array_combine( $keys, $values ) );
	}
}
