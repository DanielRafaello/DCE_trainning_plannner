<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * FR-EVT-05: what happened with an event.
 */
enum EventStatus: string
{
    case PLANNED = 'planned';
    case DONE = 'done';
    case SKIPPED = 'skipped';

    /** Default for a new event: future → planned, past → done. */
    public static function defaultFor(\DateTimeImmutable $startAt, \DateTimeImmutable $now): self
    {
        return $startAt > $now ? self::PLANNED : self::DONE;
    }
}
