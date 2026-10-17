<?php

declare(strict_types=1);

namespace App\Services\Contracts\Event;

use App\Domain\Event;
use App\Domain\EventStatus;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Exceptions\Validation\ValidationException;
use DateTimeImmutable;

/**
 * Timetable events (FR-EVT-*).
 *
 * Rules enforced here (the DB constraints only back them up):
 * - `end_at` > `start_at` and the duration is at most 24 hours; crossing midnight is fine (FR-EVT-02).
 * - The category must exist. A subcategory, if given, must belong to the user AND to that category (FR-EVT-03).
 * - Events may overlap – no overlap check (FR-EVT-06).
 * - Ownership: another user's event is reported as NotFoundException (404), never 403 (FR-EVT-09).
 *
 * Event data passed to create()/update() has these keys (already validated for type/format by the request):
 *   title: string, start_at: DateTimeImmutable, end_at: DateTimeImmutable, category_id: int,
 *   subcategory_id: ?int, description: ?string, status: ?EventStatus
 */
interface EventServiceInterface
{
    /** @throws NotFoundException */
    public function get(int $userId, int $eventId): Event;

    /**
     * Events overlapping [from, to), ordered by start (FR-EVT-08). Used by the frontend for custom ranges;
     * the week view goes through WeekServiceInterface.
     *
     * @param list<EventStatus> $statuses empty = all
     * @return list<Event>
     *
     * @throws ValidationException `to` is not after `from`, or the range is longer than 1 year
     */
    public function list(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
        ?int $subcategoryId = null,
        array $statuses = [],
    ): array;

    /**
     * Creates an event in the past or the future (FR-EVT-01, FR-EVT-04).
     * When `status` is missing it defaults to `planned` for a future start and `done` otherwise (FR-EVT-05).
     * Title and description are trimmed; an empty description is stored as null.
     *
     * @param array{
     *     title: string, start_at: DateTimeImmutable, end_at: DateTimeImmutable, category_id: int,
     *     subcategory_id?: ?int, description?: ?string, status?: ?EventStatus
     * } $data
     *
     * @throws ValidationException a rule above is broken (per-field errors)
     */
    public function create(int $userId, array $data): Event;

    /**
     * Partial update (PATCH) – only the keys present in $data change (FR-EVT-07). This is what
     * drag & drop in the timetable uses: it sends just `start_at` + `end_at`.
     *
     * The result of the merge is validated as a whole, e.g. moving an event to another category
     * without sending a new subcategory fails if the old subcategory belongs to the old category.
     * Sending `subcategory_id: null` removes the subcategory. The status is never changed implicitly –
     * moving a planned event into the past keeps it `planned` until the user marks it.
     *
     * @param array<string, mixed> $data subset of the keys listed on the interface
     *
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function update(int $userId, int $eventId, array $data): Event;

    /** @throws NotFoundException */
    public function delete(int $userId, int $eventId): void;
}
