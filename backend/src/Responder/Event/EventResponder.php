<?php

declare(strict_types=1);

namespace App\Responder\Event;

use App\Domain\Event;
use App\Responder\JsonResponder;
use DateTimeInterface;
use Psr\Http\Message\ResponseInterface as Response;

final class EventResponder extends JsonResponder
{
    /** @param list<Event> $events */
    public function list(Response $response, array $events): Response
    {
        return $this->json($response, ['events' => array_map($this->event(...), $events)]);
    }

    public function one(Response $response, Event $event, int $status = 200): Response
    {
        return $this->json($response, ['event' => $this->event($event)], $status);
    }

    public function deleted(Response $response): Response
    {
        return $response->withStatus(204);
    }

    /**
     * One event as JSON – also used by WeekResponder. Times are ISO 8601 with offset (FR-WEEK-04).
     *
     * @return array<string, mixed>
     */
    public function event(Event $event): array
    {
        return [
            'id'               => $event->id,
            'category_id'      => $event->categoryId,
            'subcategory_id'   => $event->subcategoryId,
            'title'            => $event->title,
            'description'      => $event->description,
            'start_at'         => $event->startAt->format(DateTimeInterface::ATOM),
            'end_at'           => $event->endAt->format(DateTimeInterface::ATOM),
            'duration_minutes' => $event->durationMinutes(),
            'status'           => $event->status->value,
            'created_at'       => $event->createdAt,
            'updated_at'       => $event->updatedAt,
        ];
    }
}
