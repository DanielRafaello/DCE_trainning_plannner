<?php

namespace App\Requests\Statistics;

use App\Validation\FormRequest;

/**
 * GET /stats/categories and /stats/subcategories – the period [from, to) and, for subcategories,
 * an optional category. The frontend sends ready ranges (week, month, year, custom – FR-STAT-01).
 */
class StatisticsPeriodRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'from'     => 'required|datetime_offset',
            'to'       => 'required|datetime_offset',
            'category' => 'nullable|integer',
        ];
    }
}
