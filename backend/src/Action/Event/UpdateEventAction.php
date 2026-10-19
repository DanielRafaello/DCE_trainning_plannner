<?php

declare(strict_types=1);

namespace App\Action\Event;

use App\Exceptions\Validation\ValidationException;
use App\Requests\Event\UpdateEventRequest;
use App\Responder\Event\EventResponder;
use App\Services\Contracts\Event\EventServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * PATCH /events/{id} — partial update, e.g. drag & drop sends only start_at + end_at (FR-EVT-07).
 *
 * Auth:     JWT.
 * Body:     any subset of the POST /events fields; `subcategory_id: null` removes the subcategory.
 *           The merged event is validated as a whole. The status never changes implicitly.
 *
 * Response: 200 { "event": { … same fields as in GET /events … } }
 * Errors:   401 Missing access token. / Invalid or expired token.
 *           404 not.found.event — also for another user's event.
 *           422 Validation failed. — same field errors as POST /events
 */
final readonly class UpdateEventAction
{
    public function __construct(
        private EventServiceInterface $eventService,
        private EventResponder        $responder,
    ) {
    }

    /** @param array{id: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $form = new UpdateEventRequest((array) $request->getParsedBody());

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        $event = $this->eventService->update(
            (int) $request->getAttribute('user_id'),
            (int) $args['id'],
            $form->eventData(),
        );

        return $this->responder->one($response, $event);
    }
}
