<?php

namespace App\Requests\Event;

/**
 * POST /events – title, start_at, end_at and category_id are required.
 */
class CreateEventRequest extends EventRequest
{
    protected function rules(): array
    {
        return $this->eventRules(required: true);
    }
}
