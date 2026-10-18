<?php

declare(strict_types=1);

namespace App\Services\DayNote;

use App\Domain\DayNote;
use App\Exceptions\Validation\ValidationException;
use App\Repositories\Contracts\DayNoteRepositoryInterface;
use App\Services\Contracts\DayNote\DayNoteServiceInterface;
use DateTimeImmutable;

final readonly class DayNoteService implements DayNoteServiceInterface
{
    public function __construct(
        private DayNoteRepositoryInterface $dayNotes,
    ) {
    }

    public function get(int $userId, string $date): ?DayNote
    {
        return $this->dayNotes->findByDate($userId, $this->validDate($date));
    }

    public function save(int $userId, string $date, string $content): ?DayNote
    {
        $date    = $this->validDate($date);
        $content = trim($content);

        if ($content === '') {
            $this->dayNotes->delete($userId, $date); // FR-DAY-02: empty note = no note

            return null;
        }

        if (mb_strlen($content) > DayNote::CONTENT_MAX_LENGTH) {
            throw new ValidationException(['content' => 'day_note.content_too_long']);
        }

        return $this->dayNotes->upsert($userId, $date, $content);
    }

    /** A real calendar date in `Y-m-d` – rejects e.g. 2026-02-30, which PHP would roll over to March. */
    private function validDate(string $date): string
    {
        $parsed = DateTimeImmutable::createFromFormat('!' . DayNote::DATE_FORMAT, $date);

        if ($parsed === false || $parsed->format(DayNote::DATE_FORMAT) !== $date) {
            throw new ValidationException(['date' => 'day_note.date_invalid']);
        }

        return $date;
    }
}
