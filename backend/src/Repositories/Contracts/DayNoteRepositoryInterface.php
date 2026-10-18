<?php

namespace App\Repositories\Contracts;

use App\Domain\DayNote;

/**
 * One note per user and calendar date (FR-DAY-*). Dates are `Y-m-d` strings.
 */
interface DayNoteRepositoryInterface
{
    public function findByDate(int $userId, string $date): ?DayNote;

    /**
     * Notes from $from to $to, both **inclusive** (a week = Monday … Sunday).
     *
     * @return list<DayNote> ordered by date
     */
    public function findInRange(int $userId, string $from, string $to): array;

    /** Creates the note or replaces its content (FR-DAY-02). $content must not be empty. */
    public function upsert(int $userId, string $date, string $content): DayNote;

    /** Saving an empty note deletes it (FR-DAY-02). Returns false when there was no note. */
    public function delete(int $userId, string $date): bool;
}
