<?php

declare(strict_types=1);

namespace App\ReadModel\Statistics;

use App\Domain\Category;
use App\Domain\Subcategory;

/**
 * Result of StatisticsService::bySubcategory(): one subcategory with its totals.
 * $subcategory is null for the "unassigned" row – the category's events without a subcategory (FR-STAT-03).
 * Carries the whole Subcategory, so the responder has its name and color (for charts).
 */
final readonly class SubcategoryStatistics
{
    public function __construct(
        public Category $category,
        public ?Subcategory $subcategory,
        public StatusTotals $totals,
    ) {
    }

    public function isUnassigned(): bool
    {
        return $this->subcategory === null;
    }
}
