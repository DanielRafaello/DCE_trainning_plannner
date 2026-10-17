<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * A global, note-like to-do list (FR-TO DO-*). `content` is one already sanitized HTML string.
 */
final readonly class TodoList
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $title,
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
            title:     $row['title'],
            content:   $row['content'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );
    }
}
