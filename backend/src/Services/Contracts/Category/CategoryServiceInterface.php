<?php

declare(strict_types=1);

namespace App\Services\Contracts\Category;

use App\Domain\Category;

/**
 * The fixed, system-wide categories (FR-CAT-*). Read-only – there is no create/update/delete on purpose.
 */
interface CategoryServiceInterface
{
    /**
     * All categories in display order (FR-CAT-03). The same for every user.
     *
     * @return list<Category>
     */
    public function list(): array;
}
