<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

use DataKit\DataViews\Query\Exception\QueryValidationException;

/**
 * Validates the alias a query gives an output column.
 *
 * An alias is an SQL IDENTIFIER, not a value: backends emit it as
 * ``expr AS `{$alias}` `` and reference it again in `ORDER BY` and `HAVING`.
 * `wpdb::prepare()` cannot help — it binds values, and an identifier can never
 * be bound — so the only defence is refusing an alias that could leave its
 * quotes.
 *
 * Unvalidated, `x` , (SELECT 1) AS `pwn` compiled to
 * ``col AS `x` , (SELECT 1) AS `pwn` ``: a closed identifier, an injected
 * subquery, and a reopened one. Every wpdb backend shared the sink.
 *
 * The rule is deliberately narrower than MySQL allows. An alias is generated
 * by this library or written by an integrator; neither needs punctuation, and
 * a permissive pattern here has to be right about every escaping rule in
 * every consuming backend forever.
 *
 * @since $ver$
 */
final class OutputAlias
{
    /**
     * Letters, digits, underscore, hyphen, dot and colon.
     *
     * Dot and colon are allowed because field keys already use them
     * (`field:5.1`, `form:42.field:1`) and an alias commonly defaults to the
     * field key. None of the six can terminate a backtick-quoted identifier.
     */
    private const PATTERN = '/^[A-Za-z0-9_.:-]+$/';

    /**
     * Longest alias accepted, matching MySQL's own column-alias limit.
     */
    private const MAX_LENGTH = 256;

    /**
     * @param string $alias The proposed alias.
     * @param string $context Where it came from, for the error message.
     *
     * @throws QueryValidationException When the alias is not a safe identifier.
     */
    public static function assertValid(string $alias, string $context): void
    {
        if ($alias === '') {
            throw new QueryValidationException(
                sprintf('%s alias cannot be empty.', $context)
            );
        }

        if (strlen($alias) > self::MAX_LENGTH) {
            throw new QueryValidationException(
                sprintf(
                    '%s alias is %d characters; the maximum is %d.',
                    $context,
                    strlen($alias),
                    self::MAX_LENGTH
                )
            );
        }

        if (preg_match(self::PATTERN, $alias) !== 1) {
            throw new QueryValidationException(
                sprintf(
                    '%s alias %s contains characters that are not allowed in an '
                    . 'output column name. An alias is an SQL identifier and is '
                    . 'not escapable at execution time, so only letters, digits, '
                    . 'underscore, hyphen, dot and colon are accepted.',
                    $context,
                    var_export($alias, true)
                )
            );
        }
    }
}
