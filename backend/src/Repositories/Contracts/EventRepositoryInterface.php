<?php

namespace App\Repositories\Contracts;

use App\Domain\Event;
use App\Domain\EventStatus;
use DateTimeImmutable;

/**
 * Timetable events (FR-EVT-*). Every method is scoped by $userId.
 * The repository does not validate business rules – the service does, and the DB
 * constraints (24 h max, subcategory of the same user and category) are the last line of defence.
 */
interface EventRepositoryInterface
{
    public function findForUser(int $id, int $userId): ?Event;

    /**
     * Events that **overlap** [from, to) – an event crossing the boundary is included (FR-WEEK-02, FR-EVT-08).
     * Empty $statuses = all statuses.
     *
     * @param list<EventStatus> $statuses
     * @return list<Event> ordered by start_at
     */
    public function findInRange(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
        ?int $subcategoryId = null,
        array $statuses = [],
    ): array;

    public function create(
        int $userId,
        int $categoryId,
        ?int $subcategoryId,
        string $title,
        ?string $description,
        DateTimeImmutable $startAt,
        DateTimeImmutable $endAt,
        EventStatus $status,
    ): Event;

    /**
     * Replaces all editable fields. A PATCH is merged with the current event in the service first.
     * Returns null when the event does not exist (or belongs to someone else).
     */
    public function update(
        int $id,
        int $userId,
        int $categoryId,
        ?int $subcategoryId,
        string $title,
        ?string $description,
        DateTimeImmutable $startAt,
        DateTimeImmutable $endAt,
        EventStatus $status,
    ): ?Event;

    public function delete(int $id, int $userId): bool;
}
