<?php

namespace App\Repositories;

use App\Domain\EventStatus;
use App\Domain\StatsGranularity;
use App\ReadModel\Statistics\CategoryTotals;
use App\ReadModel\Statistics\SubcategoryTotals;
use App\ReadModel\Statistics\TimelinePoint;
use App\Repositories\Contracts\StatisticsRepositoryInterface;
use DateTimeImmutable;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final readonly class StatisticsRepository implements StatisticsRepositoryInterface
{
    // P represents in PHP timezone as difference against UTC
    private const string DATE_FORMAT = 'Y-m-d H:i:sP';

    // events overlapping [from, to); the "- 24 hours" bound is implied by the 24 h event limit
    // and lets the (user_id, start_at) index do the work
    private const string PERIOD_CONDITION = 'e.user_id = :user
          AND e.start_at < CAST(:to AS TIMESTAMPTZ) AND e.end_at > CAST(:from AS TIMESTAMPTZ)
          AND e.start_at > CAST(:from AS TIMESTAMPTZ) - INTERVAL \'24 hours\'';

    // the user's events in the period, each with its duration clipped to the period in seconds (FR-STAT-06)
    private const string CLIPPED_EVENTS = 'SELECT e.category_id, e.subcategory_id, e.status,
               EXTRACT(EPOCH FROM
                   LEAST(e.end_at, CAST(:to AS TIMESTAMPTZ)) - GREATEST(e.start_at, CAST(:from AS TIMESTAMPTZ))
               ) AS seconds
        FROM events e
        WHERE ' . self::PERIOD_CONDITION;

    // one column pair per status – the shape StatusTotals::fromRow() reads
    private const string STATUS_TOTALS = '
        count(*) FILTER (WHERE status = \'planned\') AS planned_count,
        COALESCE(ROUND(SUM(seconds) FILTER (WHERE status = \'planned\') / 60), 0)::INT AS planned_minutes,
        count(*) FILTER (WHERE status = \'done\') AS done_count,
        COALESCE(ROUND(SUM(seconds) FILTER (WHERE status = \'done\') / 60), 0)::INT AS done_minutes,
        count(*) FILTER (WHERE status = \'skipped\') AS skipped_count,
        COALESCE(ROUND(SUM(seconds) FILTER (WHERE status = \'skipped\') / 60), 0)::INT AS skipped_minutes';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /** @return list<CategoryTotals> */
    public function sumByCategory(int $userId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT category_id, ' . self::STATUS_TOTALS . '
             FROM (' . self::CLIPPED_EVENTS . ') clipped
             GROUP BY category_id
             ORDER BY category_id',
            $this->periodParams($userId, $from, $to),
        );

        return array_map(CategoryTotals::fromRow(...), $rows);
    }

    /** @return list<SubcategoryTotals> */
    public function sumBySubcategory(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
    ): array {
        $params = $this->periodParams($userId, $from, $to);

        $categorySql = '';
        if ($categoryId !== null) {
            $categorySql = ' AND e.category_id = :category';
            $params['category'] = $categoryId;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT category_id, subcategory_id, ' . self::STATUS_TOTALS . '
             FROM (' . self::CLIPPED_EVENTS . $categorySql . ') clipped
             GROUP BY category_id, subcategory_id
             ORDER BY category_id, subcategory_id NULLS LAST',
            $params,
        );

        return array_map(SubcategoryTotals::fromRow(...), $rows);
    }

    /**
     * @param list<EventStatus> $statuses
     * @return list<TimelinePoint>
     */
    public function timeline(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        StatsGranularity $granularity,
        string $timezone,
        array $statuses = [],
    ): array {
        $params = $this->periodParams($userId, $from, $to);
        $params['unit'] = $granularity->value;
        $params['tz'] = $timezone;
        $types = [];

        $statusSql = '';
        if ($statuses !== []) {
            $statusSql = ' AND e.status IN (:statuses)';
            $params['statuses'] = array_map(static fn (EventStatus $s) => $s->value, $statuses);
            $types['statuses'] = ArrayParameterType::STRING;
        }

        // Buckets are generated as local times (timestamp without tz) and converted back with AT TIME ZONE,
        // so days stay correct across DST changes. Each event is clipped to the bucket AND to the period.
        $rows = $this->connection->fetchAllAssociative(
            'WITH buckets AS (
                 SELECT b AS local_start,
                        b AT TIME ZONE :tz AS bucket_start,
                        (b + CAST(\'1 \' || :unit AS INTERVAL)) AT TIME ZONE :tz AS bucket_end
                 FROM generate_series(
                     date_trunc(:unit, CAST(:from AS TIMESTAMPTZ) AT TIME ZONE :tz),
                     (CAST(:to AS TIMESTAMPTZ) - INTERVAL \'1 microsecond\') AT TIME ZONE :tz,
                     CAST(\'1 \' || :unit AS INTERVAL)
                 ) AS b
             ),
             pieces AS (
                 SELECT bk.local_start, e.category_id,
                        GREATEST(EXTRACT(EPOCH FROM
                            LEAST(e.end_at, bk.bucket_end, CAST(:to AS TIMESTAMPTZ))
                            - GREATEST(e.start_at, bk.bucket_start, CAST(:from AS TIMESTAMPTZ))
                        ), 0) AS seconds
                 FROM buckets bk
                 JOIN events e ON e.start_at < bk.bucket_end AND e.end_at > bk.bucket_start
                 WHERE ' . self::PERIOD_CONDITION . $statusSql . '
             )
             SELECT to_char(local_start, \'YYYY-MM-DD\') AS bucket,
                    category_id,
                    ROUND(SUM(seconds) / 60)::INT AS minutes
             FROM pieces
             GROUP BY local_start, category_id
             HAVING SUM(seconds) > 0
             ORDER BY local_start, category_id',
            $params,
            $types,
        );

        return array_map(TimelinePoint::fromRow(...), $rows);
    }

    /** @return array{user: int, from: string, to: string} */
    private function periodParams(int $userId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return [
            'user' => $userId,
            'from' => $from->format(self::DATE_FORMAT),
            'to'   => $to->format(self::DATE_FORMAT),
        ];
    }
}
