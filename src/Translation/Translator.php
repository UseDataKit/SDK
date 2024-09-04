<?php

namespace DataKit\DataViews\Translation;

/**
 * Represents a translator that can translate and format a message based on how `sprintf` would work.
 *
 * @since $ver$
 */
interface Translator {
	/**
	 * Translates the message.
	 *
	 * @since $ver$
	 *
	 * @param string $message   The message to translate.
	 * @param mixed  ...$values The context needed to complete the translation.
	 *
	 * @return string The translated message with the
	 */
	public function translate( string $message, ...$values ): string;
}
