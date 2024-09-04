<?php

namespace DataKit\DataViews\Translation;

/**
 * Represents a translator that can translate a message and replace placeholders.
 *
 * @since $ver$
 */
interface Translator {
	/**
	 * Translates the message.
	 *
	 * @since $ver$
	 *
	 * @param string $message    The message to translate.
	 * @param array  $parameters An array of parameters for the message.
	 *
	 * @return string The translated message with the
	 */
	public function translate( string $message, array $parameters = [] ): string;
}
