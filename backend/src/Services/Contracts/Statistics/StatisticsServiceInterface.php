<?php

declare(strict_types=1);

namespace App\Services\Contracts\Statistics;

use App\Domain\EventStatus;
use App\Domain\StatsGranularity;
use App\Exceptions\Validation\ValidationException;
use App\ReadModel\Statistics\CategoryStatistics;
use App\ReadModel\Statistics\SubcategoryStatistics;
use App\ReadModel\Statistics\TimelineBucket;
use DateTimeImmutable;

/**
 * Statistics over categories and subcategories (FR-STAT-*). Baseline only – detailed statistics
 * will be specified later, so the read models may grow, but existing fields stay.
 *
 * Common rules for every method:
 * - The period is [from, to). The frontend sends ready ranges (week, month, year or custom, FR-STAT-01);
 *   the service only checks that `to` > `from` and that the period is at most 5 years.
 * - Only the part of an event inside the period counts (FR-STAT-06).
 * - Times are in whole minutes.
 *
 * Statuses: byCategory() and bySubcategory() always return totals split by status (StatusTotals),
 * which covers both "what actually happened" – `done` only, the default view (FR-STAT-04) – and
 * plan vs. reality – done vs. skipped with completion rates (FR-STAT-07). No status filter is needed there.
 */
interface StatisticsServiceInterface
{
    /**
     * Totals per category (FR-STAT-02, FR-STAT-07).
     * All 9 categories are always present, in category order, with zeros when there are no events,
     * so charts have a stable shape.
     *
     * @return list<CategoryStatistics>
     *
     * @throws ValidationException invalid period
     */
    public function byCategory(int $userId, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * Totals per subcategory (FR-STAT-03), grouped by category in category order.
     *
     * - Every subcategory of the user is present, with zeros when it has no events in the period.
     * - Events without a subcategory form one extra "unassigned" row per category
     *   (SubcategoryStatistics::$subcategory = null), only when there are such events.
     *
     * @param int|null $categoryId only this category; null = all
     * @return list<SubcategoryStatistics>
     *
     * @throws ValidationException invalid period or unknown category
     */
    public function bySubcategory(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
    ): array;

    /**
     * Time series for charts (FR-STAT-05): minutes per category in day/week/month buckets, computed in
     * the user's timezone. Every bucket of the period is present (also empty ones), each with all categories.
     *
     * @param list<EventStatus> $statuses empty = the default, `done` only
     * @return list<TimelineBucket> in chronological order
     *
     * @throws ValidationException invalid period, or too many buckets (more than 400, e.g. days over 5 years)
     */
    public function timeline(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        StatsGranularity $granularity,
        array $statuses = [],
    ): array;
}
