<?php

declare(strict_types=1);

namespace App\ReadModel\Statistics;

/**
 * Result of StatisticsService::timeline(): one bucket (day/week/month) of the time series (FR-STAT-05).
 * The series is complete – empty buckets are present, and every bucket contains all categories.
 */
final readonly class TimelineBucket
{
    /**
     * @param string          $bucket            local start date of the bucket in the user's timezone, `Y-m-d`
     * @param array<int, int> $minutesByCategory category_id => minutes, all categories in category order
     */
    public function __construct(
        public string $bucket,
        public array $minutesByCategory,
    ) {
    }

    public function totalMinutes(): int
    {
        return array_sum($this->minutesByCategory);
    }
}
