<?php

namespace App\Repositories\Contracts;

use App\Domain\Subcategory;

/**
 * User-owned subcategories (FR-SUB-*). Every method is scoped by $userId –
 * another user's subcategory behaves exactly like a missing one.
 */
interface SubcategoryRepositoryInterface
{
    /** @return list<Subcategory> ordered by category position, then name */
    public function findByUser(int $userId, ?int $categoryId = null): array;

    public function findForUser(int $id, int $userId): ?Subcategory;

    /**
     * Case-insensitive name check within one category (FR-SUB-03).
     *
     * @param int|null $exceptId ignore this subcategory – for renaming
     */
    public function existsByName(int $userId, int $categoryId, string $name, ?int $exceptId = null): bool;

    public function create(int $userId, int $categoryId, string $name, string $color): Subcategory;

    /** Name and color only – the category can never change (FR-SUB-05). */
    public function update(int $id, int $userId, string $name, string $color): ?Subcategory;

    /** Number of events using the subcategory – a delete is allowed only when it is 0 (FR-SUB-06). */
    public function countEvents(int $id, int $userId): int;

    /**
     * Returns false when nothing was deleted. Deleting a subcategory that still has events
     * fails on the fk_events_subcategory constraint – check countEvents() first.
     */
    public function delete(int $id, int $userId): bool;
}
