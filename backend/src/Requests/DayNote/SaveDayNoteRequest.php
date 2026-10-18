<?php

namespace App\Requests\DayNote;

use App\Domain\DayNote;
use App\Validation\FormRequest;

/**
 * PUT /days/{date}/note – an empty content deletes the note (FR-DAY-02), so it is not `required`.
 * The field itself must be present, though (checked in the action): a body without it
 * must not delete the note by accident.
 */
class SaveDayNoteRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'content' => 'nullable|max:' . DayNote::CONTENT_MAX_LENGTH,
        ];
    }
}
