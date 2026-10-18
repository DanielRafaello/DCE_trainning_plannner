<?php

declare(strict_types=1);

namespace App\Responder\Statistics;

use App\Domain\Category;
use App\ReadModel\Statistics\CategoryStatistics;
use App\ReadModel\Statistics\StatusTotals;
use App\ReadModel\Statistics\SubcategoryStatistics;
use App\ReadModel\Statistics\TimelineBucket;
use App\Responder\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

final class StatisticsResponder extends JsonResponder
{
    /** @param list<CategoryStatistics> $rows */
    public function categories(Response $response, array $rows): Response
    {
        return $this->json($response, [
            'categories' => array_map(fn (CategoryStatistics $row): array => [
                'category' => $this->category($row->category),
                ...$this->totals($row->totals),
            ], $rows),
        ]);
    }

    /** @param list<SubcategoryStatistics> $rows */
    public function subcategories(Response $response, array $rows): Response
    {
        return $this->json($response, [
            'subcategories' => array_map(fn (SubcategoryStatistics $row): array => [
                'category'    => $this->category($row->category),
                // null = the category's events without a subcategory ("unassigned", FR-STAT-03)
                'subcategory' => $row->subcategory === null ? null : [
                    'id'    => $row->subcategory->id,
                    'name'  => $row->subcategory->name,
                    'color' => $row->subcategory->color,
                ],
                ...$this->totals($row->totals),
            ], $rows),
        ]);
    }

    /**
     * Per-category minutes are a list, not a map keyed by id – json_encode would turn
     * a map into an object or an array depending on its keys.
     *
     * @param list<TimelineBucket> $buckets
     */
    public function timeline(Response $response, string $groupBy, array $buckets): Response
    {
        return $this->json($response, [
            'group_by' => $groupBy,
            'buckets'  => array_map(static fn (TimelineBucket $bucket): array => [
                'bucket'        => $bucket->bucket,
                'total_minutes' => $bucket->totalMinutes(),
                'categories'    => array_map(
                    static fn (int $categoryId, int $minutes): array => ['category_id' => $categoryId, 'minutes' => $minutes],
                    array_keys($bucket->minutesByCategory),
                    array_values($bucket->minutesByCategory),
                ),
            ], $buckets),
        ]);
    }

    /** @return array<string, mixed> */
    private function category(Category $category): array
    {
        return [
            'id'   => $category->id,
            'slug' => $category->slug,
            'name' => $category->name,
        ];
    }

    /**
     * FR-STAT-02/04/07: per-status minutes and counts, plus completion rates (null = nothing done or skipped).
     *
     * @return array<string, mixed>
     */
    private function totals(StatusTotals $totals): array
    {
        return [
            'planned'         => ['minutes' => $totals->plannedMinutes, 'count' => $totals->plannedCount],
            'done'            => ['minutes' => $totals->doneMinutes, 'count' => $totals->doneCount],
            'skipped'         => ['minutes' => $totals->skippedMinutes, 'count' => $totals->skippedCount],
            'completion_rate' => [
                'by_count'   => $totals->completionRateByCount(),
                'by_minutes' => $totals->completionRateByMinutes(),
            ],
        ];
    }
}
