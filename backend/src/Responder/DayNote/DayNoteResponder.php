<?php

declare(strict_types=1);

namespace App\Responder\DayNote;

use App\Domain\DayNote;
use App\Responder\JsonResponder;
use Psr\Http\Message\ResponseInterface as Response;

final class DayNoteResponder extends JsonResponder
{
    /** A day without a note is not an error – `note` is null. */
    public function note(Response $response, ?DayNote $note): Response
    {
        return $this->json($response, [
            'note' => $note === null ? null : $this->noteData($note),
        ]);
    }

    /**
     * One note as JSON – also used by WeekResponder.
     *
     * @return array<string, mixed>
     */
    public function noteData(DayNote $note): array
    {
        return [
            'date'       => $note->date,
            'content'    => $note->content,
            'created_at' => $note->createdAt,
            'updated_at' => $note->updatedAt,
        ];
    }
}
