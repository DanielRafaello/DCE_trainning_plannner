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
 * GET /stats/subcategories?from=&to=&category= — totals per subcategory (FR-STAT-03, 07).
 *
 * Auth:     JWT.
 * Query:    from, to  ISO 8601 with offset, [from, to), at most 5 years
 *           category (optional) only this category
 *
 * Response: 200 { "subcategories": [ {
 *             "category": { "id", "slug", "name" },
 *             "subcategory": { "id", "name", "color" } | null,   null = events without a subcategory
 *             "planned": { … }, "done": { … }, "skipped": { … }, "completion_rate": { … }
 *           } ] }  grouped by category; every subcategory of the user, the unassigned row only when it has events
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           422 Validation failed. — invalid period; category: not.found.category
 */
final readonly class SubcategoryStatisticsAction
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
        $rows = $this->statisticsService->bySubcategory(
            (int) $request->getAttribute('user_id'),
            new DateTimeImmutable($data['from']),
            new DateTimeImmutable($data['to']),
            ($data['category'] ?? '') !== '' ? (int) $data['category'] : null,
        );

        return $this->responder->subcategories($response, $rows);
    }
}
