<?php

namespace App\Settings;

class SettingsFactory
{
    public function __invoke(): Settings
    {
        return new Settings(
          [
              'db' => [
                  'driver'   => $_ENV['DB_DRIVER'],
                  'host'     => $_ENV['DB_HOST'],
                  'port'     => (int) $_ENV['DB_PORT'],
                  'dbname'   => $_ENV['DB_NAME'],
                  'user'     => $_ENV['DB_USER'],
                  'password' => $_ENV['DB_PASSWORD'],
              ],
              'app' => [
                  'name'         => $_ENV['APP_NAME'] ?? 'App',
                  'env'          => $_ENV['APP_ENV'] ?? 'production',
                  'frontend_url' => $_ENV['FRONTEND_URL'] ?? 'http://localhost:5173',
              ],
              'jwt' => [
                  'secret'              => $_ENV['JWT_SECRET'],
                  'ttl_seconds'         => (int) ($_ENV['JWT_TTL_SECONDS'] ?? 604800),
                  'refresh_ttl_seconds' => (int) ($_ENV['REFRESH_TTL_SECONDS'] ?? 2592000),
              ],
          ]
        );
    }

}