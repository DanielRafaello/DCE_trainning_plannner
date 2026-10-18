<?php

declare(strict_types=1);

namespace App\Action\Statistics;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Statistics\StatisticsPeriodRequest;
use App\Responder\Statistics\StatisticsResponder;
use App\Services\Contracts\Statistics\StatisticsServiceInterface;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /stats/categories?from=&to= — totals per category (FR-STAT-01, 02, 04, 06, 07).
 *
 * Auth:     JWT.
 * Query:    from, to  ISO 8601 with offset, [from, to), at most 5 years
 *
 * Response: 200 { "categories": [ {
 *             "category": { "id", "slug", "name" },
 *             "planned": { "minutes", "count" }, "done": { … }, "skipped": { … },
 *             "completion_rate": { "by_count", "by_minutes" }   0–1, null = nothing done or skipped
 *           } ] }  all 9 categories in display order, zeros included
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           422 Validation failed. — invalid from/to, to: stats.period.to_before_from / stats.period.too_long
 */
final readonly class CategoryStatisticsAction
{
    public function __construct(
        private StatisticsServiceInterface $statisticsService,
        private StatisticsResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new StatisticsPeriodRequest($request->getQueryParams());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data = $form->safe();
        $rows = $this->statisticsService->byCategory(
            (int) $request->getAttribute('user_id'),
            new DateTimeImmutable($data['from']),
            new DateTimeImmutable($data['to']),
        );

        return $this->responder->categories($response, $rows);
    }
}
