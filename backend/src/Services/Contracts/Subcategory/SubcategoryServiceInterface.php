<?php

declare(strict_types=1);

namespace App\Services\Contracts\Subcategory;

use App\Domain\Subcategory;
use App\Exceptions\generalExceptions\ConflictException;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Exceptions\Validation\ValidationException;

/**
 * The user's own subcategories, managed from the profile (FR-SUB-*).
 *
 * Ownership: every method takes the authenticated $userId. A subcategory of another user
 * is reported as NotFoundException (404), never as 403, so its existence is not revealed.
 */
interface SubcategoryServiceInterface
{
    /**
     * The user's subcategories, ordered by category position and name (FR-SUB-04).
     *
     * @param int|null $categoryId only this category; null = all
     * @return list<Subcategory>
     *
     * @throws ValidationException the category does not exist
     */
    public function list(int $userId, ?int $categoryId = null): array;

    /**
     * Creates a subcategory under a fixed category (FR-SUB-01, FR-SUB-03, FR-SUB-03a).
     *
     * - The name is trimmed and must be unique within the category for this user (case-insensitive).
     * - $color is `#RRGGBB`; when null, a color from the default palette is assigned – the first one
     *   not yet used by the user's subcategories in that category, cycling when all are used.
     *
     * @throws ValidationException the category does not exist (`category_id`) or the name is taken (`name`)
     */
    public function create(int $userId, int $categoryId, string $name, ?string $color = null): Subcategory;

    /**
     * Renames and/or recolors a subcategory (FR-SUB-05). Null keeps the current value.
     * The category can never be changed – it would rewrite the history in the statistics.
     *
     * @throws NotFoundException   no such subcategory for this user
     * @throws ValidationException the new name is already taken in the same category (`name`)
     */
    public function update(int $userId, int $subcategoryId, ?string $name = null, ?string $color = null): Subcategory;

    /**
     * Deletes a subcategory that is not used by any event (FR-SUB-06).
     * There is no archiving and no force-delete: the user has to move or delete the events first.
     *
     * @throws NotFoundException no such subcategory for this user
     * @throws ConflictException events still reference it (409); the message contains the number of events
     */
    public function delete(int $userId, int $subcategoryId): void;
}
