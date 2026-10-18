<?php

namespace App\Requests\Event;

use App\Validation\FormRequest;

/**
 * GET /events?from=&to=&category=&subcategory=&status= – `status` is a comma-separated list
 * (e.g. `done,skipped`), parsed in the action with EventStatus::parseList().
 */
class ListEventsRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'from'        => 'required|datetime_offset',
            'to'          => 'required|datetime_offset',
            'category'    => 'nullable|integer',
            'subcategory' => 'nullable|integer',
            'status'      => 'nullable',
        ];
    }
}
