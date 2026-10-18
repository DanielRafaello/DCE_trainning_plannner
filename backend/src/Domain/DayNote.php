<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * A free-text note for one calendar date of one user (FR-DAY-*).
 */
final readonly class DayNote
{
    /** Content length in characters (FR-DAY-01) – matches the CHECK on day_notes.content */
    public const int CONTENT_MAX_LENGTH = 5000;

    public const string DATE_FORMAT = 'Y-m-d';

    public function __construct(
        public int $id,
        public int $userId,
        /** calendar date, `Y-m-d` – no time and no timezone on purpose */
        public string $date,
        public string $content,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:        (int) $row['id'],
            userId:    (int) $row['user_id'],
            date:      $row['date'],
            content:   $row['content'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );
    }
}
