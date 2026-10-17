<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Bucket size of the statistics time series (FR-STAT-05). Values are PostgreSQL date_trunc() units.
 */
enum StatsGranularity: string
{
    case DAY = 'day';
    case WEEK = 'week';   // date_trunc('week') starts on Monday, same as the timetable
    case MONTH = 'month';
}
