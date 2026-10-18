<?php

declare(strict_types=1);

namespace App\Action\Event;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Event\CreateEventRequest;
use App\Responder\Event\EventResponder;
use App\Services\Contracts\Event\EventServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * POST /events — create an event in the past or the future (FR-EVT-01 … FR-EVT-05).
 *
 * Auth:     JWT.
 * Body:     { "title", "start_at", "end_at", "category_id", "subcategory_id"?, "description"?, "status"? }
 *           times ISO 8601 with offset; status missing = planned for a future start, done otherwise
 *
 * Response: 201 { "event": { … same fields as in GET /events … } }
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           422 Validation failed. — end_at: event.end_before_start / event.too_long (> 24 h);
 *               category_id: not.found.category; subcategory_id: not.found.subcategory /
 *               event.subcategory_category_mismatch; title: event.title_length
 */
final readonly class CreateEventAction
{
    public function __construct(
        private EventServiceInterface $eventService,
        private EventResponder        $responder,
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $form = new CreateEventRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $event = $this->eventService->create((int) $request->getAttribute('user_id'), $form->eventData());

        return $this->responder->one($response, $event, 201);
    }
}
