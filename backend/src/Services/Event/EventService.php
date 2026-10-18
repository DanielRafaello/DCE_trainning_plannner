<?php

declare(strict_types=1);

namespace App\Services\Event;

use App\Domain\Event;
use App\Domain\EventStatus;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Exceptions\Validation\ValidationException;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Contracts\SubcategoryRepositoryInterface;
use App\Services\Contracts\Event\EventServiceInterface;
use DateTimeImmutable;

final readonly class EventService implements EventServiceInterface
{
    // longest range for list() – the timetable never needs more, statistics have their own endpoints
    private const string MAX_LIST_RANGE = '+1 year';

    public function __construct(
        private EventRepositoryInterface $events,
        private CategoryRepositoryInterface $categories,
        private SubcategoryRepositoryInterface $subcategories,
    ) {
    }

    public function get(int $userId, int $eventId): Event
    {
        return $this->events->findForUser($eventId, $userId) ?? throw new NotFoundException('not.found.event');
    }

    /** @return list<Event> */
    public function list(
        int $userId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
        ?int $categoryId = null,
        ?int $subcategoryId = null,
        array $statuses = [],
    ): array {
        if ($to <= $from) {
            throw new ValidationException(['to' => 'event.range.to_before_from']);
        }
        if ($to > $from->modify(self::MAX_LIST_RANGE)) {
            throw new ValidationException(['to' => 'event.range.too_long']);
        }

        return $this->events->findInRange($userId, $from, $to, $categoryId, $subcategoryId, $statuses);
    }

    public function create(int $userId, array $data): Event
    {
        $values = $this->validated($userId, [
            'title'          => $data['title'],
            'start_at'       => $data['start_at'],
            'end_at'         => $data['end_at'],
            'category_id'    => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'description'    => $data['description'] ?? null,
        ]);

        // FR-EVT-05: future → planned, past → done, unless the client says otherwise
        $status = $data['status'] ?? EventStatus::defaultFor($values['start_at'], new DateTimeImmutable());

        return $this->events->create(
            $userId,
            $values['category_id'],
            $values['subcategory_id'],
            $values['title'],
            $values['description'],
            $values['start_at'],
            $values['end_at'],
            $status,
        );
    }

    public function update(int $userId, int $eventId, array $data): Event
    {
        $current = $this->get($userId, $eventId);

        // PATCH: start from the stored event, overwrite only the keys the client sent
        $values = $this->validated($userId, [
            'title'          => array_key_exists('title', $data) ? $data['title'] : $current->title,
            'start_at'       => $data['start_at'] ?? $current->startAt,
            'end_at'         => $data['end_at'] ?? $current->endAt,
            'category_id'    => $data['category_id'] ?? $current->categoryId,
            'subcategory_id' => array_key_exists('subcategory_id', $data) ? $data['subcategory_id'] : $current->subcategoryId,
            'description'    => array_key_exists('description', $data) ? $data['description'] : $current->description,
        ]);

        // never changed implicitly – null or missing keeps the current status
        $status = $data['status'] ?? $current->status;

        return $this->events->update(
            $eventId,
            $userId,
            $values['category_id'],
            $values['subcategory_id'],
            $values['title'],
            $values['description'],
            $values['start_at'],
            $values['end_at'],
            $status,
        ) ?? throw new NotFoundException('not.found.event'); // deleted in the meantime
    }

    public function delete(int $userId, int $eventId): void
    {
        if (!$this->events->delete($eventId, $userId)) {
            throw new NotFoundException('not.found.event');
        }
    }

    /**
     * Checks the event as a whole and collects all field errors at once.
     *
     * @param array{
     *     title: ?string, start_at: DateTimeImmutable, end_at: DateTimeImmutable, category_id: int,
     *     subcategory_id: ?int, description: ?string
     * } $values
     * @return array{
     *     title: string, start_at: DateTimeImmutable, end_at: DateTimeImmutable, category_id: int,
     *     subcategory_id: ?int, description: ?string
     * }
     */
    private function validated(int $userId, array $values): array
    {
        $errors = [];

        $values['title'] = trim((string) $values['title']);
        $titleLength     = mb_strlen($values['title']);
        if ($titleLength < Event::TITLE_MIN_LENGTH || $titleLength > Event::TITLE_MAX_LENGTH) {
            $errors['title'] = 'event.title_length';
        }

        $description = $values['description'] !== null ? trim($values['description']) : '';
        $values['description'] = $description !== '' ? $description : null;
        if (mb_strlen($description) > Event::DESCRIPTION_MAX_LENGTH) {
            $errors['description'] = 'event.description_too_long';
        }

        // FR-EVT-02: compared as instants, so a DST change inside the event does not matter
        $duration = $values['end_at']->getTimestamp() - $values['start_at']->getTimestamp();
        if ($duration <= 0) {
            $errors['end_at'] = 'event.end_before_start';
        } elseif ($duration > Event::MAX_DURATION_SECONDS) {
            $errors['end_at'] = 'event.too_long';
        }

        if ($this->categories->findById($values['category_id']) === null) {
            $errors['category_id'] = 'not.found.category';
        } elseif ($values['subcategory_id'] !== null) {
            // FR-EVT-03: the subcategory must be the user's AND belong to the chosen category
            $subcategory = $this->subcategories->findForUser($values['subcategory_id'], $userId);

            if ($subcategory === null) {
                $errors['subcategory_id'] = 'not.found.subcategory';
            } elseif ($subcategory->categoryId !== $values['category_id']) {
                $errors['subcategory_id'] = 'event.subcategory_category_mismatch';
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $values;
    }
}
