<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

/**
 * One week of a user's timetable (FR-WEEK-02). Not stored – assembled by the week service.
 */
final readonly class Week
{
    /**
     * @param DateTimeImmutable $start Monday 00:00 in the user's timezone
     * @param DateTimeImmutable $end   next Monday 00:00 in the user's timezone (exclusive)
     * @param list<Event>       $events events overlapping the week, ordered by start
     * @param list<DayNote>     $notes  day notes of the 7 days, ordered by date
     */
    public function __construct(
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
        public array $events,
        public array $notes,
    ) {
    }
}
