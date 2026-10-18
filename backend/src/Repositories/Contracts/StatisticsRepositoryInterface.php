<?php

namespace App\Repositories\Contracts;

use App\Domain\EventStatus;
use App\Domain\StatsGranularity;
use App\ReadModel\Statistics\CategoryTotals;
use App\ReadModel\Statistics\SubcategoryTotals;
use App\ReadModel\Statistics\TimelinePoint;
use DateTimeImmutable;

/**
 * Raw aggregations over a user's events for the period [from, to) (FR-STAT-*).
 *
 * - Only the part of an event inside the period is counted (FR-STAT-06).
 * - Minutes are rounded to whole minutes.
 * - Totals are always split by status (StatusTotals), so there is no status filter on them –
 *   the service picks "done only", "all" or done vs. skipped from the same object.
 * - Only ids are returned; names/colors are joined in the service, which loads the categories
 *   and subcategories anyway to fill in groups without events (zeros).
 */
interface StatisticsRepositoryInterface
{
    /** @return list<CategoryTotals> categories that have events in the period, ordered by category id */
    public function sumByCategory(int $userId, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * Includes one row with subcategoryId = null per category that has events without a subcategory.
     *
     * @param int|null $categoryId only this category; null = all
     * @return list<SubcategoryTotals> ordered by category id, then subcategory id, unassigned last
     */
    public function sumBySubcategory(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
    ): array;

    /**
     * Minutes per category in time buckets (FR-STAT-05). Buckets are computed in $timezone, so a "day"
     * is the user's local day; an event crossing a bucket boundary is split between the buckets.
     * Only non-empty buckets are returned.
     *
     * @param list<EventStatus> $statuses empty = all statuses
     * @return list<TimelinePoint> ordered by bucket, then category id
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
