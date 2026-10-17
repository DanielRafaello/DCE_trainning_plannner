<?php

namespace App\Repositories;

use App\Domain\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use Doctrine\DBAL\Connection;

final readonly class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /** @return list<Category> */
    public function findAll(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, slug, name, position FROM categories ORDER BY position',
        );

        return array_map(Category::fromRow(...), $rows);
    }

    public function findById(int $id): ?Category
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, slug, name, position FROM categories WHERE id = ?',
            [$id],
        );

        return $row !== false ? Category::fromRow($row) : null;
    }
}
