<?php

declare(strict_types=1);

namespace App\Services\Category;

use App\Domain\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\Contracts\Category\CategoryServiceInterface;

final readonly class CategoryService implements CategoryServiceInterface
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
    ) {
    }

    /** @return list<Category> */
    public function list(): array
    {
        return $this->categories->findAll();
    }
}
