<?php

namespace App\Requests\TodoList;

use App\Domain\TodoList;
use App\Infrastructure\HTMLContentSanitizer;
use App\Validation\FormRequest;

/**
 * POST /to do-lists. `max` on content counts characters, so it is only a quick pre-check –
 * the exact byte limit (before and after sanitizing) is enforced in TodoListService.
 */
class CreateTodoListRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'title'   => 'required|min:' . TodoList::TITLE_MIN_LENGTH . '|max:' . TodoList::TITLE_MAX_LENGTH,
            'content' => 'nullable|max:' . HTMLContentSanitizer::MAX_INPUT_LENGTH,
        ];
    }
}
