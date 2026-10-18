<?php

declare(strict_types=1);

namespace App\Action\DayNote;

use App\Responder\DayNote\DayNoteResponder;
use App\Services\Contracts\DayNote\DayNoteServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * GET /days/{date}/note — the note of one day (FR-DAY-03). {date} is `Y-m-d`.
 *
 * Auth:     JWT.
 *
 * Response: 200 { "note": { "date", "content", "created_at", "updated_at" } | null } – null = the day has no note
 * Errors:   401 Missing bearer token. / Invalid or expired token.
 *           422 Validation failed. — date: day_note.date_invalid (e.g. 2026-02-30)
 */
final readonly class GetDayNoteAction
{
    public function __construct(
        private DayNoteServiceInterface $dayNoteService,
        private DayNoteResponder        $responder,
    ) {
    }

    /** @param array{date: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $note = $this->dayNoteService->get((int) $request->getAttribute('user_id'), $args['date']);

        return $this->responder->note($response, $note);
    }
}
