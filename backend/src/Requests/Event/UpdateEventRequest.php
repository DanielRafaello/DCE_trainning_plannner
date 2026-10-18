<?php

namespace App\Requests\Event;

/**
 * PATCH /events/{id} – every field is optional (drag & drop sends only start_at + end_at).
 */
class UpdateEventRequest extends EventRequest
{
    protected function rules(): array
    {
        return $this->eventRules(required: false);
    }
}
