<?php

declare(strict_types=1);

namespace DataKit\DataViews\Query;

/**
 * Time range filter with optional bucketing grain.
 *
 * @since $ver$
 */
final readonly class TimeRange
{
    /**
     * @param string          $field  The datetime field key to filter on.
     * @param string|null     $start  ISO 8601 UTC start datetime.
     * @param string|null     $end    ISO 8601 UTC end datetime.
     * @param TimePreset|null $preset Named preset (overrides start/end when resolved).
     * @param TimeBucket|null $grain  Bucketing granularity for time-series aggregation.
     */
    public function __construct(
        public string $field,
        public ?string $start = null,
        public ?string $end = null,
        public ?TimePreset $preset = null,
        public ?TimeBucket $grain = null,
    ) {
    }

    /**
     * Resolves the effective start/end datetimes, expanding a preset if set.
     *
     * @param \DateTimeImmutable|null $now Reference time for preset resolution.
     *
     * @return array{start: string|null, end: string|null}
     */
    public function resolveRange(?\DateTimeImmutable $now = null): array
    {
        if ($this->preset !== null) {
            [$start, $end] = $this->preset->resolve($now);

            return [
                'start' => $start->format('Y-m-d H:i:s'),
                'end' => $end->format('Y-m-d H:i:s'),
            ];
        }

        return [
            'start' => $this->start,
            'end' => $this->end,
        ];
    }

    public function toArray(): array
    {
        $data = ['field' => $this->field];

        if ($this->start !== null) {
            $data['start'] = $this->start;
        }

        if ($this->end !== null) {
            $data['end'] = $this->end;
        }

        if ($this->preset !== null) {
            $data['preset'] = $this->preset->value;
        }

        if ($this->grain !== null) {
            $data['grain'] = $this->grain->value;
        }

        return $data;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            field: $data['field'],
            start: $data['start'] ?? null,
            end: $data['end'] ?? null,
            preset: isset($data['preset']) ? TimePreset::from($data['preset']) : null,
            grain: isset($data['grain']) ? TimeBucket::from($data['grain']) : null,
        );
    }
}
