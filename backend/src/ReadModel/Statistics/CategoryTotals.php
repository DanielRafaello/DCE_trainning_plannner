<?php

declare(strict_types=1);

namespace App\ReadModel\Statistics;

/**
 * Raw aggregation row from StatisticsRepository: totals of one category in a period.
 * Only ids – names, slugs and colors are added by the service (see CategoryStatistics).
 */
final readonly class CategoryTotals
{
    public function __construct(
        public int $categoryId,
        public StatusTotals $totals,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            categoryId: (int) $row['category_id'],
            totals:     StatusTotals::fromRow($row),
        );
    }
}
