<?php

declare(strict_types=1);

namespace App\Services\Subcategory;

use App\Domain\Subcategory;
use App\Exceptions\generalExceptions\ConflictException;
use App\Exceptions\generalExceptions\NotFoundException;
use App\Exceptions\Validation\ValidationException;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\SubcategoryRepositoryInterface;
use App\Services\Contracts\Subcategory\SubcategoryServiceInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class SubcategoryService implements SubcategoryServiceInterface
{
    private const int NAME_MAX_LENGTH = 100;

    // FR-SUB-03a: colors assigned when the client sends none, in this order
    private const array DEFAULT_PALETTE = [
        '#e6194b', '#3cb44b', '#4363d8', '#f58231', '#911eb4', '#42d4f4',
        '#f032e6', '#bfef45', '#469990', '#9a6324', '#800000', '#000075',
    ];

    public function __construct(
        private SubcategoryRepositoryInterface $subcategories,
        private CategoryRepositoryInterface $categories,
    ) {
    }

    /** @return list<Subcategory> */
    public function list(int $userId, ?int $categoryId = null): array
    {
        if ($categoryId !== null) {
            $this->assertCategoryExists($categoryId);
        }

        return $this->subcategories->findByUser($userId, $categoryId);
    }

    public function create(int $userId, int $categoryId, string $name, ?string $color = null): Subcategory
    {
        $this->assertCategoryExists($categoryId);

        $name  = $this->validName($name);
        $color = $color !== null ? $this->validColor($color) : $this->defaultColor($userId, $categoryId);

        if ($this->subcategories->existsByName($userId, $categoryId, $name)) {
            throw new ValidationException(['name' => 'subcategory.name_taken']);
        }

        try {
            return $this->subcategories->create($userId, $categoryId, $name, $color);
        } catch (UniqueConstraintViolationException) {
            // a parallel request created the same name between the check and the insert
            throw new ValidationException(['name' => 'subcategory.name_taken']);
        }
    }

    public function update(int $userId, int $subcategoryId, ?string $name = null, ?string $color = null): Subcategory
    {
        $current = $this->subcategories->findForUser($subcategoryId, $userId)
            ?? throw new NotFoundException('not.found.subcategory');

        $name  = $name !== null ? $this->validName($name) : $current->name;
        $color = $color !== null ? $this->validColor($color) : $current->color;

        if ($this->subcategories->existsByName($userId, $current->categoryId, $name, $subcategoryId)) {
            throw new ValidationException(['name' => 'subcategory.name_taken']);
        }

        try {
            return $this->subcategories->update($subcategoryId, $userId, $name, $color)
                ?? throw new NotFoundException('not.found.subcategory'); // deleted in the meantime
        } catch (UniqueConstraintViolationException) {
            throw new ValidationException(['name' => 'subcategory.name_taken']);
        }
    }

    public function delete(int $userId, int $subcategoryId): void
    {
        if ($this->subcategories->findForUser($subcategoryId, $userId) === null) {
            throw new NotFoundException('not.found.subcategory');
        }

        $this->assertNoEvents($userId, $subcategoryId);

        try {
            $this->subcategories->delete($subcategoryId, $userId);
        } catch (ForeignKeyConstraintViolationException) {
            // an event was attached between the count and the delete action
            $this->assertNoEvents($userId, $subcategoryId);
            throw new ConflictException('subcategory.has_events');
        }
    }

    private function assertCategoryExists(int $categoryId): void
    {
        if ($this->categories->findById($categoryId) === null) {
            throw new ValidationException(['category_id' => 'not.found.category']);
        }
    }

    private function assertNoEvents(int $userId, int $subcategoryId): void
    {
        $events = $this->subcategories->countEvents($subcategoryId, $userId);

        if ($events > 0) {
            throw new ConflictException(sprintf('subcategory.has_events:%d', $events));
        }
    }

    /** Trimmed name, 1–100 characters (FR-SUB-03). Counted in characters, not bytes. */
    private function validName(string $name): string
    {
        $name   = trim($name);
        $length = mb_strlen($name);

        if ($length === 0 || $length > self::NAME_MAX_LENGTH) {
            throw new ValidationException(['name' => 'subcategory.name_length']);
        }

        return $name;
    }

    /** `#RRGGBB`, stored lowercase (FR-SUB-03a). */
    private function validColor(string $color): string
    {
        $color = strtolower(trim($color));

        if (preg_match(Subcategory::COLOR_PATTERN, $color) !== 1) {
            throw new ValidationException(['color' => 'subcategory.color_format']);
        }

        return $color;
    }

    /** The first palette color not used in the category yet; cycles when all are used. */
    private function defaultColor(int $userId, int $categoryId): string
    {
        $existing = $this->subcategories->findByUser($userId, $categoryId);
        $used     = array_map(static fn (Subcategory $s): string => strtolower($s->color), $existing);

        foreach (self::DEFAULT_PALETTE as $color) {
            if (!in_array($color, $used, true)) {
                return $color;
            }
        }

        return self::DEFAULT_PALETTE[count($existing) % count(self::DEFAULT_PALETTE)];
    }
}
