<?php

namespace App\Repositories\Contracts;

use App\Domain\Category;

/**
 * Read-only access to the fixed categories (FR-CAT-*).
 */
interface CategoryRepositoryInterface
{
    /** @return list<Category> ordered by position */
    public function findAll(): array;

    public function findById(int $id): ?Category;
}
