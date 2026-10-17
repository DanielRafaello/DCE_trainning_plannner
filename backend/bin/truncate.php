<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

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

// Every table except the migration history — tables stay, only rows go
$tables = $connection->fetchFirstColumn(<<<'SQL'
    SELECT quote_ident(tablename)
    FROM pg_tables
    WHERE schemaname = current_schema()
      AND tablename <> 'doctrine_migration_versions'
    ORDER BY tablename
    SQL);

if ($tables === []) {
    echo "Žádné tabulky k vyprázdnění.\n";
    exit(0);
}

echo "Mažu data z tabulek: " . implode(', ', $tables) . "\n";

$connection->executeStatement('TRUNCATE TABLE ' . implode(', ', $tables) . ' RESTART IDENTITY CASCADE');

echo "Data byla smazána, tabulky zůstaly.\n";
