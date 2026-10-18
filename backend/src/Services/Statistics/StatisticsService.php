<?php

declare(strict_types=1);

namespace App\Services\Statistics;

use App\Domain\Category;
use App\Domain\EventStatus;
use App\Domain\StatsGranularity;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Exceptions\Validation\ValidationException;
use App\ReadModel\Statistics\CategoryStatistics;
use App\ReadModel\Statistics\StatusTotals;
use App\ReadModel\Statistics\SubcategoryStatistics;
use App\ReadModel\Statistics\TimelineBucket;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use App\Repositories\Contracts\SubcategoryRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\Contracts\Statistics\StatisticsServiceInterface;
use DateTimeImmutable;
use DateTimeZone;

final readonly class StatisticsService implements StatisticsServiceInterface
{
    private const string MAX_PERIOD = '+5 years';

    private const int MAX_BUCKETS = 400;

    // same label format as to_char(…, 'YYYY-MM-DD') in StatisticsRepository::timeline()
    private const string BUCKET_FORMAT = 'Y-m-d';

    public function __construct(
        private StatisticsRepositoryInterface $statistics,
        private CategoryRepositoryInterface $categories,
        private SubcategoryRepositoryInterface $subcategories,
        private UserRepositoryInterface $users,
    ) {
    }

    /** @return list<CategoryStatistics> */
    public function byCategory(int $userId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $this->assertPeriod($from, $to);

        $totals = [];
        foreach ($this->statistics->sumByCategory($userId, $from, $to) as $row) {
            $totals[$row->categoryId] = $row->totals;
        }

        return array_map(
            static fn (Category $c): CategoryStatistics => new CategoryStatistics($c, $totals[$c->id] ?? StatusTotals::zero()),
            $this->categories->findAll(),
        );
    }

    /** @return list<SubcategoryStatistics> */
    public function bySubcategory(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
    ): array {
        $this->assertPeriod($from, $to);

        $categories = $this->categories->findAll();
        if ($categoryId !== null) {
            $categories = array_values(array_filter($categories, static fn (Category $c) => $c->id === $categoryId));
            if ($categories === []) {
                throw new ValidationException(['category' => 'not.found.category']);
            }
        }

        $totals     = []; // subcategory id => totals
        $unassigned = []; // category id => totals of events without a subcategory
        foreach ($this->statistics->sumBySubcategory($userId, $from, $to, $categoryId) as $row) {
            if ($row->subcategoryId === null) {
                $unassigned[$row->categoryId] = $row->totals;
            } else {
                $totals[$row->subcategoryId] = $row->totals;
            }
        }

        $byCategory = [];
        foreach ($this->subcategories->findByUser($userId, $categoryId) as $subcategory) {
            $byCategory[$subcategory->categoryId][] = $subcategory;
        }

        $result = [];
        foreach ($categories as $category) {
            foreach ($byCategory[$category->id] ?? [] as $subcategory) {
                $result[] = new SubcategoryStatistics(
                    $category,
                    $subcategory,
                    $totals[$subcategory->id] ?? StatusTotals::zero(),
                );
            }
            if (isset($unassigned[$category->id])) {
                $result[] = new SubcategoryStatistics($category, null, $unassigned[$category->id]);
            }
        }

        return $result;
    }

    /** @return list<TimelineBucket> */
    public function timeline(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        StatsGranularity $granularity,
        array $statuses = [],
    ): array {
        $this->assertPeriod($from, $to);

        $user     = $this->users->findById($userId) ?? throw new NotFoundException('not.found.user');
        $timezone = new DateTimeZone($user->timezone);

        // the empty series first – it also rejects too many buckets before running the query
        $labels = $this->bucketLabels($from, $to, $granularity, $timezone);

        $categoryIds = array_map(static fn (Category $c): int => $c->id, $this->categories->findAll());
        $empty       = array_fill_keys($categoryIds, 0);
        $minutes     = array_fill_keys($labels, $empty);

        $points = $this->statistics->timeline(
            $userId,
            $from,
            $to,
            $granularity,
            $user->timezone,
            $statuses !== [] ? $statuses : [EventStatus::DONE], // FR-STAT-04 default
        );
        foreach ($points as $point) {
            if (isset($minutes[$point->bucket][$point->categoryId])) {
                $minutes[$point->bucket][$point->categoryId] = $point->minutes;
            }
        }

        $buckets = [];
        foreach ($minutes as $label => $byCategory) {
            $buckets[] = new TimelineBucket((string) $label, $byCategory);
        }

        return $buckets;
    }

    private function assertPeriod(DateTimeImmutable $from, DateTimeImmutable $to): void
    {
        if ($to <= $from) {
            throw new ValidationException(['to' => 'stats.period.to_before_from']);
        }
        if ($to > $from->modify(self::MAX_PERIOD)) {
            throw new ValidationException(['to' => 'stats.period.too_long']);
        }
    }

    /**
     * Local start dates of all buckets overlapping [from, to) – the same series as generate_series()
     * in the repository: from the bucket containing `from` up to the last one starting before `to`.
     *
     * @return list<string>
     */
    private function bucketLabels(
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        StatsGranularity $granularity,
        DateTimeZone $timezone,
    ): array {
        $local = $from->setTimezone($timezone)->setTime(0, 0);

        [$current, $step] = match ($granularity) {
            StatsGranularity::DAY   => [$local, '+1 day'],
            StatsGranularity::WEEK  => [$local->modify('-' . ((int) $local->format('N') - 1) . ' days'), '+1 week'],
            StatsGranularity::MONTH => [$local->modify('first day of this month'), '+1 month'],
        };

        $labels = [];
        while ($current < $to) {
            if (count($labels) === self::MAX_BUCKETS) {
                throw new ValidationException(['groupBy' => 'stats.timeline.too_many_buckets']);
            }
            $labels[] = $current->format(self::BUCKET_FORMAT);
            $current  = $current->modify($step);
        }

        return $labels;
    }
}
