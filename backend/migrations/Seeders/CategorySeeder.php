<?php

declare(strict_types=1);

namespace App\Migrations\Seeders;

use Doctrine\DBAL\Connection;

/**
 * Fixed, system-wide categories (FR-CAT-01). Required in every environment, including production.
 * Idempotent – safe to run repeatedly, existing rows are updated by slug.
 */
final readonly class CategorySeeder
{
    /** @var list<array{slug: string, name: string}> in display order */
    private const array CATEGORIES = [
        ['slug' => 'adventure_travel',                      'name' => 'Adventure and Travel'],
        ['slug' => 'wealth_lifestyle_legacy',               'name' => 'Wealth, Lifestyle and Legacy'],
        ['slug' => 'creative_projects_artistic_expression', 'name' => 'Creative Projects and Artistic Expression'],
        ['slug' => 'career_creative_work',                  'name' => 'Career and Creative Work'],
        ['slug' => 'relationship_love_family',              'name' => 'Relationship, Love and Family'],
        ['slug' => 'health_fitness',                        'name' => 'Health and Fitness'],
        ['slug' => 'spirituality_soul_inner_work',          'name' => 'Spirituality, Soul and Inner Work'],
        ['slug' => 'connection_community',                  'name' => 'Connection and Community'],
        ['slug' => 'other',                                 'name' => 'Other'],
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function run(): void
    {
        foreach (self::CATEGORIES as $index => $category) {
            $this->connection->executeStatement(
                'INSERT INTO categories (slug, name, position) VALUES (:slug, :name, :position)
                 ON CONFLICT (slug) DO UPDATE SET name = EXCLUDED.name, position = EXCLUDED.position',
                [...$category, 'position' => $index + 1],
            );
        }
    }
}
