<?php

declare(strict_types=1);

namespace App\Migrations\Seeders\guest;

use Doctrine\DBAL\Connection;

/**
 * To-do lists of the guest. Replaces all the guest's lists on every run.
 *
 * The content is written straight to the database, bypassing HTMLContentSanitizer, so it must only
 * use what the sanitizer allows (NFR-10) – here Quill 2 lists with `data-list`.
 */
final readonly class GuestTodoListSeeder
{
    /** @var list<array{title: string, content: string}> the last one ends up on top (newest updated_at) */
    private const array LISTS = [
        [
            'title'   => 'Packing for the trip',
            'content' => '<ol><li data-list="checked">Passport</li><li data-list="checked">Charger</li>'
                . '<li data-list="unchecked">Rain jacket</li><li data-list="unchecked">Hiking boots</li></ol>',
        ],
        [
            'title'   => 'Songs to learn',
            'content' => '<p><strong>Guitar</strong> – next up:</p>'
                . '<ol><li data-list="bullet">Wonderwall</li><li data-list="bullet">Blackbird</li></ol>',
        ],
        [
            'title'   => 'Shopping',
            'content' => '<ol><li data-list="unchecked">Milk</li><li data-list="checked">Bread</li>'
                . '<li data-list="unchecked">Running socks</li></ol><p><em>Check the sale on Friday.</em></p>',
        ],
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function run(int $userId): void
    {
        $this->connection->executeStatement('DELETE FROM todo_lists WHERE user_id = ?', [$userId]);

        foreach (self::LISTS as $index => $list) {
            // spread updated_at, so the overview has a stable "most recently edited" order
            $this->connection->executeStatement(
                'INSERT INTO todo_lists (user_id, title, content, created_at, updated_at)
                 VALUES (:user, :title, :content, now() - make_interval(hours => :age), now() - make_interval(hours => :age))',
                ['user' => $userId, ...$list, 'age' => count(self::LISTS) - $index],
            );
        }
    }
}
