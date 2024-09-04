<?php

namespace DataKit\DataViews\Translation;

/**
 * Marks a class to be translatable.
 *
 * @since $ver$
 */
interface Translatable {
	/**
	 * Returns a messages through the provided translator.
	 *
	 * @since $ver$
	 *
	 * @param Translator $translator The translator.
	 *
	 * @return string The translation.
	 */
	public function translate( Translator $translator ): string;
}
