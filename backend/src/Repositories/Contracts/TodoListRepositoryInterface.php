<?php

namespace App\Repositories\Contracts;

use App\Domain\TodoList;
use App\Domain\TodoListSummary;

/**
 * Global to-do lists (FR-TO DO-*). Every method is scoped by $userId.
 * The repository stores `content` as given – sanitizing is the service's job (FR-TO DO-04).
 */
interface TodoListRepositoryInterface
{
    /** @return list<TodoListSummary> most recently updated first, without content */
    public function findSummariesByUser(int $userId): array;

    public function findForUser(int $id, int $userId): ?TodoList;

    public function create(int $userId, string $title, string $content): TodoList;

    /** Replaces title and content; returns null when the list does not exist (or belongs to someone else). */
    public function update(int $id, int $userId, string $title, string $content): ?TodoList;

    public function delete(int $id, int $userId): bool;
}
