<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * User-owned child of a fixed category, e.g. Health and Fitness → Running (FR-SUB-*).
 */
final readonly class Subcategory
{
    /** `#RRGGBB` (FR-SUB-03a) – the same rule as the CHECK constraint on subcategories.color */
    public const string COLOR_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    /** Name length in characters (FR-SUB-03) – matches VARCHAR(100) on subcategories.name */
    public const int NAME_MIN_LENGTH = 1;
    public const int NAME_MAX_LENGTH = 100;

    public function __construct(
        public int $id,
        public int $userId,
        public int $categoryId,
        public string $name,
        public string $color,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            id:         (int) $row['id'],
            userId:     (int) $row['user_id'],
            categoryId: (int) $row['category_id'],
            name:       $row['name'],
            color:      $row['color'],
            createdAt:  $row['created_at'],
            updatedAt:  $row['updated_at'],
        );
    }
}
