<?php

namespace App\Repositories\Contracts;

use App\Domain\EventStatus;
use App\Domain\StatsGranularity;
use DateTimeImmutable;

/**
 * Raw aggregations over a user's events for the period [from, to) (FR-STAT-*).
 *
 * - Only the part of an event inside the period is counted (FR-STAT-06).
 * - Minutes are rounded to whole minutes.
 * - Empty $statuses = all statuses.
 * - Rows are returned per status, so the service can build both plain totals (FR-STAT-02/03)
 *   and plan vs. reality (done vs. skipped, FR-STAT-07) from one query.
 * - Categories/subcategories without any events are not in the result – the service fills in zeros.
 */
interface StatisticsRepositoryInterface
{
    /**
     * @param list<EventStatus> $statuses
     * @return list<array{category_id: int, status: string, minutes: int, event_count: int}>
     */
    public function sumByCategory(int $userId, DateTimeImmutable $from, DateTimeImmutable $to, array $statuses = []): array;

    /**
     * subcategory_id is null for events without a subcategory ("unassigned", FR-STAT-03).
     *
     * @param list<EventStatus> $statuses
     * @return list<array{category_id: int, subcategory_id: ?int, status: string, minutes: int, event_count: int}>
     */
    public function sumBySubcategory(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
        array $statuses = [],
    ): array;

    /**
     * Minutes per category in time buckets (FR-STAT-05). Buckets are computed in $timezone, so a "day"
     * is the user's local day; an event crossing a bucket boundary is split between the buckets.
     * `bucket` is the local start date of the bucket (`Y-m-d`); empty buckets are not returned.
     *
     * @param list<EventStatus> $statuses
     * @return list<array{bucket: string, category_id: int, minutes: int}>
     */
    public function timeline(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        StatsGranularity $granularity,
        string $timezone,
        array $statuses = [],
    ): array;
}
