<?php

declare(strict_types=1);

namespace App\Services\Contracts\Statistics;

use App\Domain\EventStatus;
use App\Domain\StatsGranularity;
use App\Exceptions\Validation\ValidationException;
use DateTimeImmutable;

/**
 * Statistics over categories and subcategories (FR-STAT-*). Baseline only – detailed statistics
 * will be specified later, so the return shapes may grow (new keys), but existing keys stay.
 *
 * Common rules for every method:
 * - The period is [from, to). The frontend sends ready ranges (week, month, year or custom, FR-STAT-01);
 *   the service only checks that `to` > `from` and that the period is at most 5 years.
 * - Only the part of an event inside the period counts (FR-STAT-06).
 * - $statuses filters events; empty = the default, which is `done` only – what actually happened (FR-STAT-04).
 * - All 9 categories are always present in category results, with zeros when there are no events,
 *   so charts have a stable shape.
 * - Times are in whole minutes.
 */
interface StatisticsServiceInterface
{
    /**
     * Totals per category (FR-STAT-02).
     *
     * @param list<EventStatus> $statuses
     * @return list<array{category_id: int, slug: string, minutes: int, event_count: int}> in category order
     *
     * @throws ValidationException invalid period
     */
    public function byCategory(int $userId, DateTimeImmutable $from, DateTimeImmutable $to, array $statuses = []): array;

    /**
     * Totals per subcategory (FR-STAT-03). Events without a subcategory are reported as one extra
     * row per category with `subcategory_id: null` ("unassigned"). Subcategories with no events
     * in the period are included with zeros.
     *
     * @param int|null          $categoryId only this category; null = all
     * @param list<EventStatus> $statuses
     * @return list<array{category_id: int, subcategory_id: ?int, name: ?string, color: ?string, minutes: int, event_count: int}>
     *
     * @throws ValidationException invalid period or unknown category
     */
    public function bySubcategory(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
        array $statuses = [],
    ): array;

    /**
     * Plan vs. reality per category (FR-STAT-07): what was done and what was skipped.
     * `completion_rate` = done / (done + skipped), by count and by minutes, as 0–1;
     * null when there is nothing done or skipped. `planned` events are ignored – they have not happened yet.
     *
     * @return list<array{
     *     category_id: int, slug: string,
     *     done_minutes: int, done_count: int, skipped_minutes: int, skipped_count: int,
     *     completion_rate_count: ?float, completion_rate_minutes: ?float
     * }>
     *
     * @throws ValidationException invalid period
     */
    public function planVsReality(int $userId, DateTimeImmutable $from, DateTimeImmutable $to): array;

    /**
     * Time series for charts (FR-STAT-05): minutes per category in day/week/month buckets, computed in
     * the user's timezone. Every bucket of the period is present (also empty ones), each with all categories.
     *
     * @param list<EventStatus> $statuses
     * @return list<array{bucket: string, categories: array<int, int>}> bucket = local start date `Y-m-d`,
     *         categories = category_id => minutes
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
