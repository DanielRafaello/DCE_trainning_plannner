<?php

namespace App\Repositories;

use App\Domain\EventStatus;
use App\Domain\StatsGranularity;
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

    // minutes of the event clipped to the period (FR-STAT-06)
    private const string CLIPPED_MINUTES = 'ROUND(SUM(EXTRACT(EPOCH FROM
          LEAST(e.end_at, CAST(:to AS TIMESTAMPTZ)) - GREATEST(e.start_at, CAST(:from AS TIMESTAMPTZ))
        )) / 60)::INT';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param list<EventStatus> $statuses
     * @return list<array{category_id: int, status: string, minutes: int, event_count: int}>
     */
    public function sumByCategory(int $userId, DateTimeImmutable $from, DateTimeImmutable $to, array $statuses = []): array
    {
        [$statusSql, $params, $types] = $this->baseParams($userId, $from, $to, $statuses);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT e.category_id, e.status, ' . self::CLIPPED_MINUTES . ' AS minutes, count(*) AS event_count
             FROM events e
             WHERE ' . self::PERIOD_CONDITION . $statusSql . '
             GROUP BY e.category_id, e.status
             ORDER BY e.category_id, e.status',
            $params,
            $types,
        );

        return array_map(static fn (array $r) => [
            'category_id' => (int) $r['category_id'],
            'status'      => $r['status'],
            'minutes'     => (int) $r['minutes'],
            'event_count' => (int) $r['event_count'],
        ], $rows);
    }

    /**
     * @param list<EventStatus> $statuses
     * @return list<array{category_id: int, subcategory_id: ?int, status: string, minutes: int, event_count: int}>
     */
    public function sumBySubcategory(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
        array $statuses = [],
    ): array {
        [$statusSql, $params, $types] = $this->baseParams($userId, $from, $to, $statuses);

        $categorySql = '';
        if ($categoryId !== null) {
            $categorySql = ' AND e.category_id = :category';
            $params['category'] = $categoryId;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT e.category_id, e.subcategory_id, e.status,
                    ' . self::CLIPPED_MINUTES . ' AS minutes, count(*) AS event_count
             FROM events e
             WHERE ' . self::PERIOD_CONDITION . $statusSql . $categorySql . '
             GROUP BY e.category_id, e.subcategory_id, e.status
             ORDER BY e.category_id, e.subcategory_id NULLS LAST, e.status',
            $params,
            $types,
        );

        return array_map(static fn (array $r) => [
            'category_id'    => (int) $r['category_id'],
            'subcategory_id' => $r['subcategory_id'] !== null ? (int) $r['subcategory_id'] : null,
            'status'         => $r['status'],
            'minutes'        => (int) $r['minutes'],
            'event_count'    => (int) $r['event_count'],
        ], $rows);
    }

    /**
     * @param list<EventStatus> $statuses
     * @return list<array{bucket: string, category_id: int, minutes: int}>
     */
    public function timeline(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        StatsGranularity $granularity,
        string $timezone,
        array $statuses = [],
    ): array {
        [$statusSql, $params, $types] = $this->baseParams($userId, $from, $to, $statuses);
        $params['unit'] = $granularity->value;
        $params['tz'] = $timezone;

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
             )
             SELECT to_char(bk.local_start, \'YYYY-MM-DD\') AS bucket,
                    e.category_id,
                    ROUND(SUM(GREATEST(EXTRACT(EPOCH FROM
                        LEAST(e.end_at, bk.bucket_end, CAST(:to AS TIMESTAMPTZ))
                        - GREATEST(e.start_at, bk.bucket_start, CAST(:from AS TIMESTAMPTZ))
                    ), 0)) / 60)::INT AS minutes
             FROM buckets bk
             JOIN events e ON e.start_at < bk.bucket_end AND e.end_at > bk.bucket_start
             WHERE ' . self::PERIOD_CONDITION . $statusSql . '
             GROUP BY bk.local_start, e.category_id
             HAVING SUM(GREATEST(EXTRACT(EPOCH FROM
                        LEAST(e.end_at, bk.bucket_end, CAST(:to AS TIMESTAMPTZ))
                        - GREATEST(e.start_at, bk.bucket_start, CAST(:from AS TIMESTAMPTZ))
                    ), 0)) > 0
             ORDER BY bk.local_start, e.category_id',
            $params,
            $types,
        );

        return array_map(static fn (array $r) => [
            'bucket'      => $r['bucket'],
            'category_id' => (int) $r['category_id'],
            'minutes'     => (int) $r['minutes'],
        ], $rows);
    }

    /**
     * @param list<EventStatus> $statuses
     * @return array{0: string, 1: array<string, mixed>, 2: array<string, ArrayParameterType>}
     */
    private function baseParams(int $userId, DateTimeImmutable $from, DateTimeImmutable $to, array $statuses): array
    {
        $params = [
            'user' => $userId,
            'from' => $from->format(self::DATE_FORMAT),
            'to'   => $to->format(self::DATE_FORMAT),
        ];

        if ($statuses === []) {
            return ['', $params, []];
        }

        $params['statuses'] = array_map(static fn (EventStatus $s) => $s->value, $statuses);

        return [' AND e.status IN (:statuses)', $params, ['statuses' => ArrayParameterType::STRING]];
    }
}
