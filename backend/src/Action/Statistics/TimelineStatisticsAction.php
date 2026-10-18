<?php

declare(strict_types=1);

namespace App\Action\Statistics;

use App\Domain\EventStatus;
use App\Domain\StatsGranularity;
use App\Exceptions\Validation\ValidationException;
use App\Requests\Statistics\TimelineRequest;
use App\Responder\Statistics\StatisticsResponder;
use App\Services\Contracts\Statistics\StatisticsServiceInterface;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /stats/timeline?from=&to=&groupBy=day|week|month&status= — time series for charts (FR-STAT-05).
 *
 * Auth:     JWT.
 * Query:    from, to  ISO 8601 with offset, [from, to), at most 5 years and 400 buckets
 *           groupBy   day | week | month – buckets in the user's timezone, weeks start on Monday
 *           status    (optional) comma-separated; missing = done only
 *
 * Response: 200 { "group_by", "buckets": [ {
 *             "bucket": "2026-10-12",   local start date of the bucket
 *             "total_minutes",
 *             "categories": [ { "category_id", "minutes" } ]   all 9 categories in display order
 *           } ] }  every bucket of the period, also empty ones
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           422 Validation failed. — invalid period/status/groupBy, groupBy: stats.timeline.too_many_buckets
 */
final readonly class TimelineStatisticsAction
{
    public function __construct(
        private StatisticsServiceInterface $statisticsService,
        private StatisticsResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new TimelineRequest($request->getQueryParams());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data     = $form->safe();
        $statuses = EventStatus::parseList((string) $data['status'])
            ?? throw new ValidationException(['status' => 'event.status_invalid']);

        $buckets = $this->statisticsService->timeline(
            userId:      (int) $request->getAttribute('user_id'),
            from:        new DateTimeImmutable($data['from']),
            to:          new DateTimeImmutable($data['to']),
            granularity: StatsGranularity::from($data['groupBy']),
            statuses:    $statuses,
        );

        return $this->responder->timeline($response, $data['groupBy'], $buckets);
    }
}
