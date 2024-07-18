<?php

namespace DataKit\DataViews\Data\DataMatcher;

/**
 * Helper class that can perform basic pattern matching on a data item.
 *
 * @since $ver$
 * @todo  Add support for search operators like +, - and "exact phrases".
 */
final class ArrayDataMatcher {
	/**
	 * Returns whether the provided data array matches a search query string.
	 *
	 * @since $ver$
	 *
	 * @param array  $data            The data.
	 * @param string $query           The query string.
	 * @param bool   $is_exact_search Whether to treat the query as an exact term.
	 *
	 * @return bool Whether the query is found in the data.
	 */
	public static function is_data_matched_by_string(
		array $data,
		string $query,
		bool $is_exact_search = false
	): bool {
		if ( ! $query ) {
			return true;
		}

		if ( ! $is_exact_search ) {
			foreach ( self::parse_query_string( $query ) as $sub_query ) {
				if ( self::is_data_matched_by_string( $data, $sub_query, true ) ) {
					return true;
				}
			}
		}

		foreach ( $data as $value ) {
			if ( stripos( (string) $value, $query ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Parse a full query string into multiple possible parts.
	 *
	 * @since $ver$
	 *
	 * @param string $query The query to parse.
	 *
	 * @return array The query parts.
	 */
	private static function parse_query_string( string $query ): array {
		return preg_split( '/\s+/', $query );
	}
}
