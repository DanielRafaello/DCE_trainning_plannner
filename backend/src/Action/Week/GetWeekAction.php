<?php

declare(strict_types=1);

namespace App\Action\Week;

use App\Responder\Week\WeekResponder;
use App\Services\Contracts\Week\WeekServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /weeks/{date} — the weekly timetable (FR-WEEK-01 … FR-WEEK-04).
 *
 * Auth:     JWT.
 * Path:     {date} any day of the week `2026-10-18`, or an ISO week `2026-W42`
 *
 * Response: 200 {
 *             "week":   { "iso_week", "start", "end" },  Monday 00:00 … next Monday 00:00 in the user's timezone
 *             "events": [ … same fields as in GET /events … ],  everything overlapping the week
 *             "notes":  [ { "date", "content", "created_at", "updated_at" } ]  only days that have a note
 *           }
 * Errors:   401 Missing access token. / Invalid or expired token.
 *           422 Validation failed. — date: week.date_invalid
 */
final readonly class GetWeekAction
{
    public function __construct(
        private WeekServiceInterface $weekService,
        private WeekResponder        $responder,
    ) {
    }

    /** @param array{date: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $week = $this->weekService->getWeek((int) $request->getAttribute('user_id'), $args['date']);

        return $this->responder->respond($response, $week);
    }
}
