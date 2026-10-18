<?php

declare(strict_types=1);

namespace App\ReadModel\Statistics;

/**
 * Raw aggregation row from StatisticsRepository: totals of one subcategory in a period.
 * $subcategoryId is null for the category's events without a subcategory ("unassigned", FR-STAT-03).
 */
final readonly class SubcategoryTotals
{
    public function __construct(
        public int $categoryId,
        public ?int $subcategoryId,
        public StatusTotals $totals,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            categoryId:    (int) $row['category_id'],
            subcategoryId: $row['subcategory_id'] !== null ? (int) $row['subcategory_id'] : null,
            totals:        StatusTotals::fromRow($row),
        );
    }
}
