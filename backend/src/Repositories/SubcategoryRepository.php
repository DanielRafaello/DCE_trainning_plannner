<?php

namespace App\Repositories;

use App\Domain\Subcategory;
use App\Repositories\Contracts\SubcategoryRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class SubcategoryRepository implements SubcategoryRepositoryInterface
{
    private const string COLUMNS = 'id, user_id, category_id, name, color, created_at, updated_at';

    public function __construct(
        private Connection $connection,
    ) {
    }

    /** @return list<Subcategory> */
    public function findByUser(int $userId, ?int $categoryId = null): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT s.id, s.user_id, s.category_id, s.name, s.color, s.created_at, s.updated_at
             FROM subcategories s
             JOIN categories c ON c.id = s.category_id
             WHERE s.user_id = :user AND (CAST(:category AS SMALLINT) IS NULL OR s.category_id = :category)
             ORDER BY c.position, lower(s.name)',
            ['user' => $userId, 'category' => $categoryId],
        );

        return array_map(Subcategory::fromRow(...), $rows);
    }

    public function findForUser(int $id, int $userId): ?Subcategory
    {
        $row = $this->connection->fetchAssociative(
            'SELECT ' . self::COLUMNS . ' FROM subcategories WHERE id = ? AND user_id = ?',
            [$id, $userId],
        );

        return $row !== false ? Subcategory::fromRow($row) : null;
    }

    public function existsByName(int $userId, int $categoryId, string $name, ?int $exceptId = null): bool
    {
        return $this->connection->fetchOne(
            'SELECT 1 FROM subcategories
             WHERE user_id = :user AND category_id = :category AND lower(name) = lower(:name)
               AND id IS DISTINCT FROM :except',
            ['user' => $userId, 'category' => $categoryId, 'name' => $name, 'except' => $exceptId],
        ) !== false;
    }

    public function create(int $userId, int $categoryId, string $name, string $color): Subcategory
    {
        $row = $this->connection->fetchAssociative(
            'INSERT INTO subcategories (user_id, category_id, name, color)
             VALUES (?, ?, ?, ?)
             RETURNING ' . self::COLUMNS,
            [$userId, $categoryId, $name, $color],
        );

        return Subcategory::fromRow($row);
    }

    public function update(int $id, int $userId, string $name, string $color): ?Subcategory
    {
        $row = $this->connection->fetchAssociative(
            'UPDATE subcategories SET name = ?, color = ?, updated_at = now()
             WHERE id = ? AND user_id = ?
             RETURNING ' . self::COLUMNS,
            [$name, $color, $id, $userId],
        );

        return $row !== false ? Subcategory::fromRow($row) : null;
    }

    public function countEvents(int $id, int $userId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT count(*) FROM events WHERE subcategory_id = ? AND user_id = ?',
            [$id, $userId],
        );
    }

    public function delete(int $id, int $userId): bool
    {
        return $this->connection->executeStatement(
            'DELETE FROM subcategories WHERE id = ? AND user_id = ?',
            [$id, $userId],
        ) > 0;
    }
}
