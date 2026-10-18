<?php

declare(strict_types=1);

namespace App\Services\TodoList;

use App\Domain\TodoList;
use App\Domain\TodoListSummary;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Exceptions\Validation\ValidationException;
use App\Infrastructure\HTMLContentSanitizer;
use App\Repositories\Contracts\TodoListRepositoryInterface;
use App\Services\Contracts\TodoList\TodoListServiceInterface;

final readonly class TodoListService implements TodoListServiceInterface
{
    public function __construct(
        private TodoListRepositoryInterface $todoLists,
        private HTMLContentSanitizer $sanitizer,
    ) {
    }

    /** @return list<TodoListSummary> */
    public function list(int $userId): array
    {
        return $this->todoLists->findSummariesByUser($userId);
    }

    public function get(int $userId, int $listId): TodoList
    {
        return $this->todoLists->findForUser($listId, $userId)
            ?? throw new NotFoundException('not.found.todo_list');
    }

    public function create(int $userId, string $title, string $content = ''): TodoList
    {
        return $this->todoLists->create($userId, $this->validTitle($title), $this->cleanContent($content));
    }

    public function update(int $userId, int $listId, ?string $title = null, ?string $content = null): TodoList
    {
        $current = $this->get($userId, $listId);

        $title   = $title !== null ? $this->validTitle($title) : $current->title;
        $content = $content !== null ? $this->cleanContent($content) : $current->content;

        return $this->todoLists->update($listId, $userId, $title, $content)
            ?? throw new NotFoundException('not.found.todo_list'); // deleted in the meantime
    }

    public function delete(int $userId, int $listId): void
    {
        if (!$this->todoLists->delete($listId, $userId)) {
            throw new NotFoundException('not.found.todo_list');
        }
    }

    /** Trimmed title, counted in characters (FR-TO DO-01). */
    private function validTitle(string $title): string
    {
        $title  = trim($title);
        $length = mb_strlen($title);

        if ($length < TodoList::TITLE_MIN_LENGTH || $length > TodoList::TITLE_MAX_LENGTH) {
            throw new ValidationException(['title' => 'todo_list.title_length']);
        }

        return $title;
    }

    /**
     * Sanitized HTML (FR-TO DO-04). The limit is in bytes like the DB CHECK (octet_length) and is checked
     * before sanitizing (the sanitizer would silently truncate) and after it (it may add e.g. `rel`).
     */
    private function cleanContent(string $content): string
    {
        if (strlen($content) > HTMLContentSanitizer::MAX_INPUT_LENGTH) {
            throw new ValidationException(['content' => 'todo_list.content_too_long']);
        }

        $clean = $this->sanitizer->sanitize($content);

        if (strlen($clean) > HTMLContentSanitizer::MAX_INPUT_LENGTH) {
            throw new ValidationException(['content' => 'todo_list.content_too_long']);
        }

        return $clean;
    }
}
