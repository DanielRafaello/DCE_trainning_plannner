<?php

declare(strict_types=1);

namespace App\ReadModel\Statistics;

use App\Domain\Category;

/**
 * Result of StatisticsService::byCategory(): one category with its totals.
 * Carries the whole Category, so the responder has slug and name without extra lookups.
 */
final readonly class CategoryStatistics
{
    public function __construct(
        public Category $category,
        public StatusTotals $totals,
    ) {
    }
}
