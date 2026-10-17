<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

/**
 * A timetable entry (FR-EVT-*). Max. 24 h long, may cross midnight.
 * start/end are real date objects because services compute with them (week ranges, statistics).
 */
final readonly class Event
{
    public function __construct(
        public int $id,
        public int $userId,
        public int $categoryId,
        public ?int $subcategoryId,
        public string $title,
        public ?string $description,
        public DateTimeImmutable $startAt,
        public DateTimeImmutable $endAt,
        public EventStatus $status,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:            (int) $row['id'],
            userId:        (int) $row['user_id'],
            categoryId:    (int) $row['category_id'],
            subcategoryId: $row['subcategory_id'] !== null ? (int) $row['subcategory_id'] : null,
            title:         $row['title'],
            description:   $row['description'],
            startAt:       new DateTimeImmutable($row['start_at']),
            endAt:         new DateTimeImmutable($row['end_at']),
            status:        EventStatus::from($row['status']),
            createdAt:     $row['created_at'],
            updatedAt:     $row['updated_at'],
        );
    }

    public function durationMinutes(): int
    {
        return intdiv($this->endAt->getTimestamp() - $this->startAt->getTimestamp(), 60);
    }
}
