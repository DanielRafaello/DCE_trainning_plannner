<?php

declare(strict_types=1);

namespace App\Migrations\Seeders\guest;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;

/**
 * Day notes of the guest in last and this week. Upserted by date (one note per day, FR-DAY-02).
 */
final readonly class GuestDayNoteSeeder
{
    /** @var array<int, string> day offset from the current Monday => plain-text note */
    private const array NOTES = [
        -4 => 'Skipped the run – slept badly.',
        -2 => "Long run felt great.\nConcert in the evening, home late.",
        0  => 'New week: 3 runs, 2× guitar.',
        2  => 'Gym skipped, knee hurts a bit.',
        5  => 'Hike if the weather is good.',
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function run(int $userId, DateTimeImmutable $monday): void
    {
        foreach (self::NOTES as $day => $content) {
            $this->connection->executeStatement(
                'INSERT INTO day_notes (user_id, date, content) VALUES (:user, :date, :content)
                 ON CONFLICT (user_id, date) DO UPDATE SET content = EXCLUDED.content, updated_at = now()',
                [
                    'user'    => $userId,
                    'date'    => $monday->modify(sprintf('%+d days', $day))->format('Y-m-d'),
                    'content' => $content,
                ],
            );
        }
    }
}
