<?php

namespace DataKit\DataViews\DataView;

/**
 * Object that represents a search query.
 *
 * @since $ver$
 */
final class Search {
	/**
	 * The scopes.
	 *
	 * @since $ver$
	 */
	private const SCOPE_IGNORED  = 'ignored';
	private const SCOPE_REQUIRED = 'required';
	private const SCOPE_OPTIONAL = 'optional';

	/**
	 * Whether a `Search` object should be parsed as a string.
	 *
	 * @since $ver$
	 * @var bool
	 */
	private bool $use_search_as_string = false;

	/**
	 * Enables the parsing of search objects.
	 *
	 * @since $ver$
	 */
	public function with_parsing(): self {
		$clone                       = clone $this;
		$clone->use_search_as_string = false;

		return $clone;
	}

	/**
	 * Disables the parsing of search objects.
	 *
	 * @since $ver$
	 */
	public function without_parsing(): self {
		$clone                       = clone $this;
		$clone->use_search_as_string = true;

		return $clone;
	}

	/**
	 * The search query.
	 *
	 * @since $ver$
	 * @var string
	 */
	private string $query;

	/**
	 * The parsed scopes.
	 *
	 * @since $ver$
	 * @var array
	 */
	private array $scopes = [];

	/**
	 * Lookup table used to select the correct scope.
	 *
	 * @since $ver$
	 * @var array<string, string>
	 */
	private array $scope_lookup = [
		''  => self::SCOPE_OPTIONAL,
		'+' => self::SCOPE_REQUIRED,
		'-' => self::SCOPE_IGNORED,
	];

	/**
	 * Needs to be created from a string.
	 *
	 * @since $ver$
	 */
	private function __construct( string $query ) {
		$this->query = trim( $query );
	}

	/**
	 * Creates an instance from a string.
	 *
	 * @since $ver$
	 *
	 * @param string $query The search query.
	 *
	 * @return self The search instance.
	 */
	public static function from_string( string $query ): self {
		return new self( $query );
	}

	/**
	 * Returns the original search query.
	 *
	 * @since $ver$
	 * @return string The search query.
	 */
	public function __toString(): string {
		return $this->query;
	}

	/**
	 * Resets the parse state when the object is cloned.
	 *
	 * @since $ver$
	 */
	public function __clone() {
		$this->scopes = [];
	}

	/**
	 * Parses the search query into multiple scopes.
	 *
	 * @since $ver$
	 */
	private function parse(): void {
		if ( [] !== $this->scopes ) {
			// already parsed.
			return;
		}

		$this->scopes = [
			self::SCOPE_REQUIRED => [],
			self::SCOPE_OPTIONAL => [],
			self::SCOPE_IGNORED  => [],
		];

		if ( $this->use_search_as_string ) {
			foreach ( preg_split( '/\s+/', $this->query ) as $match ) {
				$this->scopes[ self::SCOPE_OPTIONAL ][] = $match;
			}

			return;
		}

		if ( ! preg_match_all( '/(?<precision>([+\-]))?(?<part>([^[\s"]+|"(.*?)"))/sm', $this->query, $matches ) ) {
			return;
		}

		foreach ( $matches['part'] as $i => $match ) {
			$precision                = $matches['precision'][ $i ] ?? '';
			$scope                    = $this->scope_lookup[ $precision ];
			$this->scopes[ $scope ][] = trim( $match, '"' );
		}
	}

	/**
	 * Returns the parsed parts.
	 *
	 * @since $ver$
	 *
	 * @return array{required:string[], optional:string[], ignored:string[]}
	 */
	public function to_array(): array {
		$this->parse();

		return $this->scopes;
	}

	/**
	 * Returns the query strings that MUST be available on the data.
	 *
	 * @since $ver$
	 * @return string[] The required query strings.
	 */
	public function required(): array {
		$this->parse();

		return $this->scopes[ self::SCOPE_REQUIRED ];
	}

	/**
	 * Returns the query strings that MUST NOT be available on the data.
	 *
	 * @since $ver$
	 * @return string[] The ignored query strings.
	 */
	public function ignored(): array {
		$this->parse();

		return $this->scopes[ self::SCOPE_IGNORED ];
	}

	/**
	 * Returns the query strings that CAN be available on the data.
	 *
	 * @since $ver$
	 * @return string[] The optional query strings.
	 */
	public function optional(): array {
		$this->parse();

		return $this->scopes[ self::SCOPE_OPTIONAL ];
	}

	/**
	 * Whether the search is empty.
	 *
	 * @since $ver
	 */
	public function is_empty(): bool {
		return '' === $this->query;
	}
}
