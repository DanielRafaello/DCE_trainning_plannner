<?php

declare(strict_types=1);

namespace App\ReadModel\Statistics;

use App\Domain\EventStatus;

/**
 * Minutes and event counts split by event status – one aggregated group (a category, a subcategory, …).
 * Value object: no identity, immutable, computed from events, never stored.
 *
 * One object answers both plain totals (FR-STAT-02/03/04 – e.g. "done only") and
 * plan vs. reality (FR-STAT-07 – done vs. skipped).
 */
final readonly class StatusTotals
{
    public function __construct(
        public int $plannedMinutes,
        public int $plannedCount,
        public int $doneMinutes,
        public int $doneCount,
        public int $skippedMinutes,
        public int $skippedCount,
    ) {
    }

    /** For groups without any events in the period – the service fills these in. */
    public static function zero(): self
    {
        return new self(0, 0, 0, 0, 0, 0);
    }

    /** @param array<string, mixed> $row columns planned_minutes, planned_count, done_minutes, … */
    public static function fromRow(array $row): self
    {
        return new self(
            plannedMinutes: (int) $row['planned_minutes'],
            plannedCount:   (int) $row['planned_count'],
            doneMinutes:    (int) $row['done_minutes'],
            doneCount:      (int) $row['done_count'],
            skippedMinutes: (int) $row['skipped_minutes'],
            skippedCount:   (int) $row['skipped_count'],
        );
    }

    /** Sum of minutes over the given statuses; none given = all statuses. */
    public function minutes(EventStatus ...$statuses): int
    {
        return array_sum(array_map(fn (EventStatus $s) => match ($s) {
            EventStatus::PLANNED => $this->plannedMinutes,
            EventStatus::DONE    => $this->doneMinutes,
            EventStatus::SKIPPED => $this->skippedMinutes,
        }, $statuses ?: EventStatus::cases()));
    }

    /** Sum of event counts over the given statuses; none given = all statuses. */
    public function count(EventStatus ...$statuses): int
    {
        return array_sum(array_map(fn (EventStatus $s) => match ($s) {
            EventStatus::PLANNED => $this->plannedCount,
            EventStatus::DONE    => $this->doneCount,
            EventStatus::SKIPPED => $this->skippedCount,
        }, $statuses ?: EventStatus::cases()));
    }

    /**
     * done / (done + skipped) by number of events, 0–1 (FR-STAT-07).
     * Null when nothing was done or skipped – "no data" is not the same as 0 %.
     * Planned events are ignored, they have not happened yet.
     */
    public function completionRateByCount(): ?float
    {
        return self::rate($this->doneCount, $this->skippedCount);
    }

    /** Same as completionRateByCount(), weighted by minutes. */
    public function completionRateByMinutes(): ?float
    {
        return self::rate($this->doneMinutes, $this->skippedMinutes);
    }

    private static function rate(int $done, int $skipped): ?float
    {
        return $done + $skipped === 0 ? null : $done / ($done + $skipped);
    }
}
