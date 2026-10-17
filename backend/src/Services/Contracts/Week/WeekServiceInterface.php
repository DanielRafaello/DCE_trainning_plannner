<?php

declare(strict_types=1);

namespace App\Services\Contracts\Week;

use App\Domain\Week;
use App\Exceptions\Validation\ValidationException;

/**
 * The weekly timetable – the main screen of the app (FR-WEEK-*).
 */
interface WeekServiceInterface
{
    /**
     * Returns the week that contains $date, for any past or future week (FR-WEEK-01, FR-WEEK-03).
     *
     * - $date is either a calendar date `Y-m-d` (any day of the week) or an ISO week `2026-W42`.
     * - The week always starts on Monday 00:00 and ends on the next Monday 00:00 (exclusive),
     *   both in the **user's timezone** – so the UTC range differs per user and across DST changes (FR-WEEK-04).
     * - Contains all events overlapping the week, including one that started on Sunday before
     *   and ends on Monday, and the day notes of the 7 days (FR-WEEK-02).
     *
     * @throws ValidationException $date is in neither format or is not a real date
     */
    public function getWeek(int $userId, string $date): Week;
}
