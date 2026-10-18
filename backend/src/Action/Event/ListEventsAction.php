<?php

declare(strict_types=1);

namespace App\Action\Event;

use App\Domain\EventStatus;
use App\Exceptions\Validation\ValidationException;
use App\Requests\Event\ListEventsRequest;
use App\Responder\Event\EventResponder;
use App\Services\Contracts\Event\EventServiceInterface;
use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /events?from=&to=&category=&subcategory=&status= — events overlapping [from, to) (FR-EVT-08).
 *
 * Auth:     JWT.
 * Query:    from, to     ISO 8601 with offset, at most 1 year apart
 *           category, subcategory (optional) ids
 *           status (optional) comma-separated, e.g. `done,skipped`; missing = all
 *
 * Response: 200 { "events": [ { "id", "category_id", "subcategory_id", "title", "description",
 *                               "start_at", "end_at", "duration_minutes", "status", "created_at", "updated_at" } ] }
 *           ordered by start_at
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           422 Validation failed. — invalid from/to/status, to: event.range.to_before_from / event.range.too_long
 */
final readonly class ListEventsAction
{
    public function __construct(
        private EventServiceInterface $eventService,
        private EventResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new ListEventsRequest($request->getQueryParams());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $data     = $form->safe();
        $statuses = EventStatus::parseList((string) $data['status'])
            ?? throw new ValidationException(['status' => 'event.status_invalid']);

        $events = $this->eventService->list(
            userId:        (int) $request->getAttribute('user_id'),
            from:          new DateTimeImmutable($data['from']),
            to:            new DateTimeImmutable($data['to']),
            categoryId:    ($data['category'] ?? '') !== '' ? (int) $data['category'] : null,
            subcategoryId: ($data['subcategory'] ?? '') !== '' ? (int) $data['subcategory'] : null,
            statuses:      $statuses,
        );

        return $this->responder->list($response, $events);
    }
}
