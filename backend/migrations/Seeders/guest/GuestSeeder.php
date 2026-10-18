<?php

declare(strict_types=1);

namespace App\Migrations\Seeders\guest;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use RuntimeException;

/**
 * Demo data for the guest account (guest@gmail.com): 2 subcategories, events around the current week,
 * to-do lists and day notes. Development/demo only – not part of the default `composer db:seed`.
 *
 * The user itself is NOT created here – it has to exist (register it via POST /auth/register).
 * Repeatable: the guest's events and to-do lists are replaced, subcategories and day notes are upserted.
 * Dates are relative to the week the seeder runs in, so the timetable always shows recent data.
 */
final readonly class GuestSeeder
{
    public const string EMAIL = 'guest@gmail.com';

    public const string TIMEZONE = 'Europe/Prague';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function run(): void
    {
        $userId = $this->connection->fetchOne('SELECT id FROM users WHERE lower(email) = lower(?)', [self::EMAIL]);

        if ($userId === false) {
            throw new RuntimeException(sprintf(
                'Guest user %s does not exist – register it first (POST /auth/register).',
                self::EMAIL,
            ));
        }

        $userId = (int) $userId;
        $monday = self::currentMonday();

        $subcategories = new GuestSubcategorySeeder($this->connection)->run($userId);
        new GuestEventSeeder($this->connection)->run($userId, $monday, $subcategories);
        new GuestTodoListSeeder($this->connection)->run($userId);
        new GuestDayNoteSeeder($this->connection)->run($userId, $monday);
    }

    /** Monday 00:00 of the current week in the guest's timezone. */
    private static function currentMonday(): DateTimeImmutable
    {
        $today = new DateTimeImmutable('today', new DateTimeZone(self::TIMEZONE));

        return $today->modify('-' . ((int) $today->format('N') - 1) . ' days');
    }
}
