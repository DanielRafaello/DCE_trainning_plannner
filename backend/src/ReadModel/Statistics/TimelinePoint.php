<?php

declare(strict_types=1);

namespace App\ReadModel\Statistics;

/**
 * Raw aggregation row from StatisticsRepository: minutes of one category in one time bucket (FR-STAT-05).
 * Only non-empty buckets are returned; the service turns them into a complete series (TimelineBucket).
 */
final readonly class TimelinePoint
{
    public function __construct(
        /** local start date of the bucket in the user's timezone, `Y-m-d` */
        public string $bucket,
        public int $categoryId,
        public int $minutes,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            bucket:     $row['bucket'],
            categoryId: (int) $row['category_id'],
            minutes:    (int) $row['minutes'],
        );
    }
}
