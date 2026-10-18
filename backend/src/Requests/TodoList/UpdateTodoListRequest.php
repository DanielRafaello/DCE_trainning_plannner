<?php

namespace App\Requests\TodoList;

use App\Domain\TodoList;
use App\Infrastructure\HTMLContentSanitizer;
use App\Validation\FormRequest;

/**
 * PATCH /todo-lists/{id} – both fields are optional, a missing one keeps the current value.
 * An empty content ("") clears the list. The exact byte limit is enforced in TodoListService.
 */
class UpdateTodoListRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'title'   => 'nullable|min:' . TodoList::TITLE_MIN_LENGTH . '|max:' . TodoList::TITLE_MAX_LENGTH,
            'content' => 'nullable|max:' . HTMLContentSanitizer::MAX_INPUT_LENGTH,
        ];
    }
}
