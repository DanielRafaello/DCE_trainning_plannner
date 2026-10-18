<?php

declare(strict_types=1);

namespace App\Migrations\Seeders\guest;

use Doctrine\DBAL\Connection;

/**
 * Two subcategories of the guest. Upserted by (user, category, name), so their ids – and the events
 * pointing to them – survive a repeated run.
 */
final readonly class GuestSubcategorySeeder
{
    /** @var array<string, array{category: string, name: string, color: string}> key => subcategory */
    private const array SUBCATEGORIES = [
        'running' => ['category' => 'health_fitness',                        'name' => 'Running', 'color' => '#e6194b'],
        'guitar'  => ['category' => 'creative_projects_artistic_expression', 'name' => 'Guitar',  'color' => '#4363d8'],
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    /** @return array<string, int> key => subcategory id */
    public function run(int $userId): array
    {
        $ids = [];

        foreach (self::SUBCATEGORIES as $key => $subcategory) {
            $ids[$key] = (int) $this->connection->fetchOne(
                'INSERT INTO subcategories (user_id, category_id, name, color)
                 VALUES (:user, (SELECT id FROM categories WHERE slug = :category), :name, :color)
                 ON CONFLICT (user_id, category_id, lower(name))
                     DO UPDATE SET color = EXCLUDED.color, updated_at = now()
                 RETURNING id',
                ['user' => $userId, ...$subcategory],
            );
        }

        return $ids;
    }
}
