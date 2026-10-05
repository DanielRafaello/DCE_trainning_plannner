<?php

declare(strict_types=1);

/*
 * Bootstrap for the doctrine/migrations CLI (vendor/bin/doctrine-migrations).
 * Builds its own DBAL connection from .env — the CLI runs outside the Slim
 * app, so it can't reach the PHP-DI container built in app/bootstrap.php.
 */

require __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, ['.env', '.env.development']);
$dotenv->load();

$connection = DriverManager::getConnection([
    'driver'   => $_ENV['DB_DRIVER'],
    'host'     => $_ENV['DB_HOST'],
    'port'     => (int) $_ENV['DB_PORT'],
    'dbname'   => $_ENV['DB_NAME'],
    'user'     => $_ENV['DB_USER'],
    'password' => $_ENV['DB_PASSWORD'],
]);

$config = new PhpFile(__DIR__ . '/migrations.php');

return DependencyFactory::fromConnection($config, new ExistingConnection($connection));
