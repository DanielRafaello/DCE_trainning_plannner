<?php

declare(strict_types=1);

namespace App\Services\Contracts\TodoList;

use App\Domain\TodoList;
use App\Domain\TodoListSummary;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Exceptions\Validation\ValidationException;

/**
 * Global, note-like to-do lists (FR-TO DO-*). The body of a list is one HTML string from the
 * frontend's Quill editor; individual items exist only inside that HTML.
 *
 * Content handling (FR-TO DO-04, NFR-10):
 * - Every saved `content` goes through HTMLContentSanitizer – raw user HTML is never stored.
 * - Input longer than HTMLContentSanitizer::MAX_INPUT_LENGTH (100 kB) is rejected, not truncated.
 * - The length is checked again AFTER sanitizing (the sanitizer may add e.g. `rel` to links),
 *   so the value always fits the DB limit.
 *
 * Ownership: another user's list is reported as NotFoundException (404).
 */
interface TodoListServiceInterface
{
    /**
     * All lists of the user without content, most recently edited first (FR-TO DO-01, FR-TO DO-05).
     *
     * @return list<TodoListSummary>
     */
    public function list(int $userId): array;

    /** One list including its content. @throws NotFoundException */
    public function get(int $userId, int $listId): TodoList;

    /**
     * Creates a list; the title is trimmed (1–100 chars), the content may be empty.
     *
     * @throws ValidationException
     */
    public function create(int $userId, string $title, string $content = ''): TodoList;

    /**
     * Renames the list and/or replaces its content (FR-TO DO-03). Null keeps the current value.
     * Any change moves the list to the top of the overview (updated_at).
     *
     * @throws NotFoundException
     * @throws ValidationException
     */
    public function update(int $userId, int $listId, ?string $title = null, ?string $content = null): TodoList;

    /** @throws NotFoundException */
    public function delete(int $userId, int $listId): void;
}
