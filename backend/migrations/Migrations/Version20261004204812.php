<?php

declare(strict_types=1);

namespace App\Migrations\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * RATE LIMITS
 */
final class Version20261004204812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create rate_limits table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE rate_limits (
                id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                ip_hash      CHAR(64)    NOT NULL,
                requests     INT         NOT NULL DEFAULT 1,
                window_start TIMESTAMPTZ NOT NULL
            )
            SQL);

        $this->addSql('CREATE INDEX idx_rate_limits_ip_window ON rate_limits (ip_hash, window_start)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE rate_limits');
    }
}
