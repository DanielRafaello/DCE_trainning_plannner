<?php

declare(strict_types=1);

namespace App\Responder\Week;

use App\Domain\Week;
use App\Responder\DayNote\DayNoteResponder;
use App\Responder\Event\EventResponder;
use App\Responder\JsonResponder;
use DateTimeInterface;
use Psr\Http\Message\ResponseInterface as Response;

final class WeekResponder extends JsonResponder
{
    public function __construct(
        private readonly EventResponder $events,
        private readonly DayNoteResponder $notes,
    ) {
    }

    public function respond(Response $response, Week $week): Response
    {
        return $this->json($response, [
            'week' => [
                // ISO week of the Monday, e.g. 2026-W42 – handy for prev/next navigation
                'iso_week' => $week->start->format('o-\WW'),
                'start'    => $week->start->format(DateTimeInterface::ATOM),
                'end'      => $week->end->format(DateTimeInterface::ATOM),
            ],
            'events' => array_map($this->events->event(...), $week->events),
            'notes'  => array_map($this->notes->noteData(...), $week->notes),
        ]);
    }
}
