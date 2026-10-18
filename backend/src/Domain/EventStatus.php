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

    /**
     * Parses a comma-separated query value, e.g. `done,skipped`. Duplicates are removed.
     *
     * @return list<self>|null null when any of the values is not a status
     */
    public static function parseList(string $value): ?array
    {
        $statuses = [];

        foreach (array_filter(array_map('trim', explode(',', $value)), 'strlen') as $item) {
            $status = self::tryFrom($item);
            if ($status === null) {
                return null;
            }
            $statuses[$status->value] = $status;
        }

        return array_values($statuses);
    }

    /** Default for a new event: future → planned, past → done. */
    public static function defaultFor(\DateTimeImmutable $startAt, \DateTimeImmutable $now): self
    {
        return $startAt > $now ? self::PLANNED : self::DONE;
    }
}
