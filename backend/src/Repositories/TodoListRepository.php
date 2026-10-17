<?php

namespace App\Repositories;

use App\Domain\TodoList;
use App\Domain\TodoListSummary;
use App\Repositories\Contracts\TodoListRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class TodoListRepository implements TodoListRepositoryInterface
{
    private const string COLUMNS = 'id, user_id, title, content, created_at, updated_at';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /** @return list<TodoListSummary> */
    public function findSummariesByUser(int $userId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, title, updated_at FROM todo_lists WHERE user_id = ? ORDER BY updated_at DESC, id DESC',
            [$userId],
        );

        return array_map(TodoListSummary::fromRow(...), $rows);
    }

    public function findForUser(int $id, int $userId): ?TodoList
    {
        $row = $this->connection->fetchAssociative(
            'SELECT ' . self::COLUMNS . ' FROM todo_lists WHERE id = ? AND user_id = ?',
            [$id, $userId],
        );

        return $row !== false ? TodoList::fromRow($row) : null;
    }

    public function create(int $userId, string $title, string $content): TodoList
    {
        $row = $this->connection->fetchAssociative(
            'INSERT INTO todo_lists (user_id, title, content) VALUES (?, ?, ?) RETURNING ' . self::COLUMNS,
            [$userId, $title, $content],
        );

        return TodoList::fromRow($row);
    }

    public function update(int $id, int $userId, string $title, string $content): ?TodoList
    {
        $row = $this->connection->fetchAssociative(
            'UPDATE todo_lists SET title = ?, content = ?, updated_at = now()
             WHERE id = ? AND user_id = ?
             RETURNING ' . self::COLUMNS,
            [$title, $content, $id, $userId],
        );

        return $row !== false ? TodoList::fromRow($row) : null;
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->connection->executeStatement(
            'DELETE FROM todo_lists WHERE id = ? AND user_id = ?',
            [$id, $userId],
        ) > 0;
    }
}
