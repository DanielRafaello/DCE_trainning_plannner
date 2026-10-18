<?php

declare(strict_types=1);

namespace App\Migrations\Seeders\guest;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;

/**
 * Events of the guest from last week to next week. Replaces all the guest's events on every run.
 *
 * The status follows FR-EVT-05: an event in the future is always `planned`; one in the past gets
 * the status from the list (`done` or `skipped`), so statistics show plan vs. reality.
 */
final readonly class GuestEventSeeder
{
    private const string DATE_FORMAT = 'Y-m-d H:i:sP';

    /**
     * Day offset from the current Monday, local start time, minutes, category slug, subcategory key,
     * title, description, status once the event is in the past.
     *
     * @var list<array{0: int, 1: string, 2: int, 3: string, 4: ?string, 5: string, 6: ?string, 7: string}>
     */
    private const array EVENTS = [
        // last week
        [-7, '06:30', 45,  'health_fitness',                        'running', 'Morning run',         '6 km easy pace', 'done'],
        [-6, '09:00', 480, 'career_creative_work',                  null,      'Work',                null,             'done'],
        [-5, '18:00', 60,  'creative_projects_artistic_expression', 'guitar',  'Guitar practice',     'Scales + one new song', 'done'],
        [-4, '06:30', 45,  'health_fitness',                        'running', 'Morning run',         null,             'skipped'],
        [-3, '19:00', 120, 'relationship_love_family',              null,      'Dinner with family',  null,             'done'],
        [-2, '10:00', 90,  'health_fitness',                        'running', 'Long run',            '12 km',          'done'],
        [-2, '22:30', 150, 'connection_community',                  null,      'Concert',             'Crosses midnight into Sunday', 'done'],
        [-1, '20:00', 30,  'spirituality_soul_inner_work',          null,      'Meditation',          null,             'skipped'],
        // this week
        [0,  '06:30', 45,  'health_fitness',                        'running', 'Morning run',         null,             'done'],
        [0,  '09:00', 480, 'career_creative_work',                  null,      'Work',                null,             'done'],
        [1,  '18:00', 60,  'creative_projects_artistic_expression', 'guitar',  'Guitar practice',     null,             'done'],
        [2,  '07:00', 60,  'health_fitness',                        null,      'Gym',                 'Upper body',     'skipped'],
        [3,  '18:30', 90,  'wealth_lifestyle_legacy',               null,      'Budget review',       null,             'done'],
        [4,  '06:30', 45,  'health_fitness',                        'running', 'Morning run',         null,             'done'],
        [5,  '09:00', 240, 'adventure_travel',                      null,      'Hike in the hills',   null,             'done'],
        [6,  '20:00', 30,  'spirituality_soul_inner_work',          null,      'Meditation',          null,             'done'],
        // next week
        [7,  '06:30', 45,  'health_fitness',                        'running', 'Morning run',         null,             'done'],
        [9,  '18:00', 60,  'creative_projects_artistic_expression', 'guitar',  'Guitar lesson',       null,             'done'],
        [11, '17:00', 120, 'other',                                 null,      'Car service',         null,             'done'],
    ];

    public function __construct(
        private Connection $connection,
    ) {
    }

    /** @param array<string, int> $subcategories key => id from GuestSubcategorySeeder */
    public function run(int $userId, DateTimeImmutable $monday, array $subcategories): void
    {
        $this->connection->executeStatement('DELETE FROM events WHERE user_id = ?', [$userId]);

        $now = new DateTimeImmutable();

        foreach (self::EVENTS as [$day, $time, $minutes, $category, $subcategory, $title, $description, $pastStatus]) {
            [$hour, $minute] = array_map('intval', explode(':', $time));
            $start = $monday->modify(sprintf('%+d days', $day))->setTime($hour, $minute);
            $end   = $start->modify("+{$minutes} minutes");

            $this->connection->executeStatement(
                'INSERT INTO events (user_id, category_id, subcategory_id, title, description, start_at, end_at, status)
                 VALUES (:user, (SELECT id FROM categories WHERE slug = :category), :subcategory, :title,
                         :description, :start, :end, :status)',
                [
                    'user'        => $userId,
                    'category'    => $category,
                    'subcategory' => $subcategory !== null ? $subcategories[$subcategory] : null,
                    'title'       => $title,
                    'description' => $description,
                    'start'       => $start->format(self::DATE_FORMAT),
                    'end'         => $end->format(self::DATE_FORMAT),
                    'status'      => $start > $now ? 'planned' : $pastStatus,
                ],
            );
        }
    }
}
