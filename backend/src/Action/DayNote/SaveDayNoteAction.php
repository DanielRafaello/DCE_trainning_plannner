<?php

declare(strict_types=1);

namespace App\Action\DayNote;

use App\Exceptions\Validation\ValidationException;
use App\Requests\DayNote\SaveDayNoteRequest;
use App\Responder\DayNote\DayNoteResponder;
use App\Services\Contracts\DayNote\DayNoteServiceInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * PUT /days/{date}/note — create or replace the note of a day (FR-DAY-01, FR-DAY-02). {date} is `Y-m-d`.
 *
 * Auth:     JWT.
 * Body:     { "content" } — plain text, max. 5,000 chars; "" deletes the note
 *
 * Response: 200 { "note": { "date", "content", "created_at", "updated_at" } | null } – null = deleted
 * Errors:   401 Missing access token. / Invalid or expired token.
 *           422 Validation failed. — content missing or too long; date: day_note.date_invalid
 */
final readonly class SaveDayNoteAction
{
    public function __construct(
        private DayNoteServiceInterface $dayNoteService,
        private DayNoteResponder        $responder,
    ) {
    }

    /** @param array{date: string} $args */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $body = (array) $request->getParsedBody();
        $form = new SaveDayNoteRequest($body);

        if ($form->fails()) {
            throw new ValidationException($form->errors());
        }

        // "" deletes the note, a missing field must not
        if (!array_key_exists('content', $body)) {
            throw new ValidationException(['content' => 'Content is required.']);
        }

        $note = $this->dayNoteService->save(
            (int) $request->getAttribute('user_id'),
            $args['date'],
            (string) $form->safe()['content'],
        );

        return $this->responder->note($response, $note);
    }
}
