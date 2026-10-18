<?php

namespace App\Requests\Statistics;

use App\Domain\StatsGranularity;
use App\Validation\FormRequest;

/**
 * GET /stats/timeline?from=&to=&groupBy=day|week|month&status= – `status` is a comma-separated list,
 * missing = `done` only (FR-STAT-04).
 */
class TimelineRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'from'    => 'required|datetime_offset',
            'to'      => 'required|datetime_offset',
            'groupBy' => 'required|in:' . implode(',', array_column(StatsGranularity::cases(), 'value')),
            'status'  => 'nullable',
        ];
    }
}
