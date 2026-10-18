<?php

namespace App\Requests\Event;

use App\Domain\Event;
use App\Domain\EventStatus;
use App\Validation\FormRequest;
use DateTimeImmutable;

/**
 * Shared by create (POST) and update (PATCH): the same fields, only `required` differs.
 * Title/description length, the 24 h limit and the subcategory/category pair are checked
 * again in EventService, which sees the merged event.
 */
abstract class EventRequest extends FormRequest
{
    /** @return array<string, string> rules for one field; $required = POST, otherwise PATCH */
    protected function eventRules(bool $required): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'title'          => $presence . '|min:' . Event::TITLE_MIN_LENGTH . '|max:' . Event::TITLE_MAX_LENGTH,
            'start_at'       => $presence . '|datetime_offset',
            'end_at'         => $presence . '|datetime_offset',
            'category_id'    => $presence . '|integer',
            'subcategory_id' => 'nullable|integer',
            'description'    => 'nullable|max:' . Event::DESCRIPTION_MAX_LENGTH,
            'status'         => 'nullable|in:' . implode(',', array_column(EventStatus::cases(), 'value')),
        ];
    }

    /**
     * The fields the client actually sent, converted to the types EventServiceInterface expects.
     * Missing keys stay missing – for PATCH that means "keep"; `subcategory_id: null` removes the subcategory.
     * Call only after the request passed.
     *
     * @return array<string, mixed>
     */
    public function eventData(): array
    {
        $body = $this->all();
        $data = [];

        if (array_key_exists('title', $body)) {
            $data['title'] = (string) $body['title'];
        }
        foreach (['start_at', 'end_at'] as $field) {
            if (($body[$field] ?? '') !== '') {
                $data[$field] = new DateTimeImmutable((string) $body[$field]);
            }
        }
        if (($body['category_id'] ?? '') !== '') {
            $data['category_id'] = (int) $body['category_id'];
        }
        if (array_key_exists('subcategory_id', $body)) {
            $data['subcategory_id'] = ($body['subcategory_id'] ?? '') !== '' ? (int) $body['subcategory_id'] : null;
        }
        if (array_key_exists('description', $body)) {
            $data['description'] = $body['description'] !== null ? (string) $body['description'] : null;
        }
        if (($body['status'] ?? '') !== '') {
            $data['status'] = EventStatus::from((string) $body['status']);
        }

        return $data;
    }
}
