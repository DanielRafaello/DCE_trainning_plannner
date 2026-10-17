<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Migrations\Seeders\DatabaseSeeder;
use Doctrine\DBAL\DriverManager;

// Load env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', ['.env', '.env.development']);
$dotenv->load();

$connection = DriverManager::getConnection([
    'driver'   => $_ENV['DB_DRIVER'],
    'host'     => $_ENV['DB_HOST'],
    'port'     => (int) $_ENV['DB_PORT'],
    'dbname'   => $_ENV['DB_NAME'],
    'user'     => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASSWORD'],
]);

echo "Spouštím seedování databáze...\n";

$seeder = new DatabaseSeeder($connection);
$seeder->run();

echo "Seedování bylo úspěšně dokončeno!\n";