<?php

declare(strict_types=1);

namespace App\Migrations\Seeders;

use Doctrine\DBAL\Connection;

/**
 * Entry point for `composer db:seed` – runs all seeders in order, in one transaction.
 */
final readonly class DatabaseSeeder
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function run(): void
    {
        $this->connection->transactional(function (Connection $connection): void {
            new CategorySeeder($connection)->run();
            // $this->call(AnotherSeeder::class);
        });
    }
}
