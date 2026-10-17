<?php

declare(strict_types=1);

namespace App\Services\Contracts\DayNote;

use App\Domain\DayNote;
use App\Exceptions\Validation\ValidationException;

/**
 * One plain-text note per calendar day (FR-DAY-*). Dates are `Y-m-d` – a day note has no time or timezone.
 */
interface DayNoteServiceInterface
{
    /**
     * The note of one day, or null when the day has none (FR-DAY-03).
     * A missing note is not an error – most days have none.
     *
     * @throws ValidationException $date is not a valid `Y-m-d` date
     */
    public function get(int $userId, string $date): ?DayNote;

    /**
     * Creates or replaces the note of a day (`PUT /days/{date}/note`, FR-DAY-01, FR-DAY-02).
     *
     * - The content is plain text (no HTML sanitizing – the frontend renders it as text), max. 5,000 chars.
     * - Content that is empty after trimming **deletes** the note and null is returned.
     *
     * @throws ValidationException $date is invalid or the content is too long
     */
    public function save(int $userId, string $date, string $content): ?DayNote;
}
