<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * Predefined time range presets.
 *
 * @since $ver$
 */
enum TimePreset: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = 'last_7_days';
    case Last30Days = 'last_30_days';
    case Last90Days = 'last_90_days';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case ThisYear = 'this_year';
    case LastYear = 'last_year';
    case Last1Year = 'last_1_year';
    case Last2Years = 'last_2_years';
    case ThisQuarter = 'this_quarter';
    case LastQuarter = 'last_quarter';

    /**
     * Resolves this preset to a concrete start/end DateTimeImmutable pair.
     *
     * @param \DateTimeImmutable|null $now Reference time (defaults to current UTC time).
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable} [start, end]
     */
    public function resolve(?\DateTimeImmutable $now = null): array
    {
        $now = $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $utc = new \DateTimeZone('UTC');

        return match ($this) {
            self::Today => [
                $now->setTime(0, 0, 0, 0),
                $now->setTime(23, 59, 59, 999999),
            ],
            self::Yesterday => [
                $now->modify('-1 day')->setTime(0, 0, 0, 0),
                $now->modify('-1 day')->setTime(23, 59, 59, 999999),
            ],
            self::Last7Days => [
                $now->modify('-6 days')->setTime(0, 0, 0, 0),
                $now->setTime(23, 59, 59, 999999),
            ],
            self::Last30Days => [
                $now->modify('-29 days')->setTime(0, 0, 0, 0),
                $now->setTime(23, 59, 59, 999999),
            ],
            self::Last90Days => [
                $now->modify('-89 days')->setTime(0, 0, 0, 0),
                $now->setTime(23, 59, 59, 999999),
            ],
            self::ThisMonth => [
                $now->modify('first day of this month')->setTime(0, 0, 0, 0),
                $now->setTime(23, 59, 59, 999999),
            ],
            self::LastMonth => [
                $now->modify('first day of last month')->setTime(0, 0, 0, 0),
                $now->modify('last day of last month')->setTime(23, 59, 59, 999999),
            ],
            self::ThisYear => [
                new \DateTimeImmutable($now->format('Y') . '-01-01 00:00:00', $utc),
                $now->setTime(23, 59, 59, 999999),
            ],
            self::LastYear => [
                new \DateTimeImmutable(($now->format('Y') - 1) . '-01-01 00:00:00', $utc),
                new \DateTimeImmutable(($now->format('Y') - 1) . '-12-31 23:59:59.999999', $utc),
            ],
            self::Last1Year => [
                $now->modify('-1 year')->setTime(0, 0, 0, 0),
                $now->setTime(23, 59, 59, 999999),
            ],
            self::Last2Years => [
                $now->modify('-2 years')->setTime(0, 0, 0, 0),
                $now->setTime(23, 59, 59, 999999),
            ],
            self::ThisQuarter => [
                self::quarterStart($now, $utc),
                $now->setTime(23, 59, 59, 999999),
            ],
            self::LastQuarter => [
                self::quarterStart($now->modify('-3 months'), $utc),
                self::quarterStart($now, $utc)->modify('-1 day')->setTime(23, 59, 59, 999999),
            ],
        };
    }

    /**
     * Returns the first day of the quarter containing the given date.
     */
    private static function quarterStart(\DateTimeImmutable $date, \DateTimeZone $utc): \DateTimeImmutable
    {
        $month = (int) $date->format('n');
        $quarterMonth = (int) (floor(($month - 1) / 3) * 3 + 1);

        return new \DateTimeImmutable(
            sprintf('%s-%02d-01 00:00:00', $date->format('Y'), $quarterMonth),
            $utc,
        );
    }
}
