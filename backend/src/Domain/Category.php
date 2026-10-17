<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Fixed, system-wide category (FR-CAT-01). Read-only for everyone – rows come from CategorySeeder.
 */
final readonly class Category
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public int $position,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:       (int) $row['id'],
            slug:     $row['slug'],
            name:     $row['name'],
            position: (int) $row['position'],
        );
    }
}
