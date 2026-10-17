<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * A to-do list without its (up to 100 kB) content – for the list overview (FR-TODO-05).
 */
final readonly class TodoListSummary
{
    public function __construct(
        public int $id,
        public string $title,
        public string $updatedAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:        (int) $row['id'],
            title:     $row['title'],
            updatedAt: $row['updated_at'],
        );
    }
}
