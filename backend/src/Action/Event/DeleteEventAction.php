<?php

declare(strict_types=1);

namespace App\Action\Event;

use App\Responder\Event\EventResponder;
use App\Services\Contracts\Event\EventServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * DELETE /events/{id} — delete an event (FR-EVT-07).
 *
 * Auth:     JWT.
 *
 * Response: 204 (no body)
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           404 not.found.event — also for another user's event.
 */
final readonly class DeleteEventAction
{
    public function __construct(
        private EventServiceInterface $eventService,
        private EventResponder        $responder,
    ) {
    }

    /** @param array{id: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->eventService->delete((int) $request->getAttribute('user_id'), (int) $args['id']);

        return $this->responder->deleted($response);
    }
}
