<?php

declare(strict_types=1);

namespace App\Services\Week;

use App\Domain\DayNote;
use App\Domain\Week;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Exceptions\Validation\ValidationException;
use App\Repositories\Contracts\DayNoteRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\Week\WeekServiceInterface;
use DateTimeImmutable;
use DateTimeZone;

final readonly class WeekService implements WeekServiceInterface
{
    // `2026-W42`
    private const string ISO_WEEK_PATTERN = '/^(\d{4})-W(\d{2})$/';

    public function __construct(
        private UserRepositoryInterface $users,
        private EventRepositoryInterface $events,
        private DayNoteRepositoryInterface $dayNotes,
    ) {
    }

    public function getWeek(int $userId, string $date): Week
    {
        $user     = $this->users->findById($userId) ?? throw new NotFoundException('not.found.user');
        $timezone = new DateTimeZone($user->timezone);

        $day   = $this->parseDay($date, $timezone);
        $start = $day->modify('-' . ((int) $day->format('N') - 1) . ' days'); // back to Monday 00:00, example modify('-2 days')
        // "+7 days" keeps the local 00:00 – the week is 167 or 169 hours long across a DST change
        $end   = $start->modify('+7 days');

        $sunday = $end->modify('-1 day')->format(DayNote::DATE_FORMAT);

        return new Week(
            start:  $start,
            end:    $end,
            events: $this->events->findInRange($userId, $start, $end),
            notes:  $this->dayNotes->findInRange($userId, $start->format(DayNote::DATE_FORMAT), $sunday),
        );
    }

    /** Midnight of the given day (or of the Monday of the ISO week) in the user's timezone. */
    private function parseDay(string $date, DateTimeZone $timezone): DateTimeImmutable
    {
        if (preg_match(self::ISO_WEEK_PATTERN, $date, $match) === 1) {
            $day = new DateTimeImmutable('today', $timezone)->setISODate((int) $match[1], (int) $match[2]);

            // setISODate() rolls W54 over into the next year – reject instead
            if ($day->format('o-\WW') !== $date) {
                throw new ValidationException(['date' => 'week.date_invalid']);
            }

            return $day;
        }

        $day = DateTimeImmutable::createFromFormat('!' . DayNote::DATE_FORMAT, $date, $timezone);

        if ($day === false || $day->format(DayNote::DATE_FORMAT) !== $date) {
            throw new ValidationException(['date' => 'week.date_invalid']);
        }

        return $day;
    }
}
