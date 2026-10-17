<?php

declare(strict_types=1);

namespace App\Migrations\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * REFRESH TOKENS
 */
final class Version20261005064050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Refresh_tokens table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE refresh_tokens (
                id          BIGINT       GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                user_id     BIGINT       NOT NULL REFERENCES users (id) ON DELETE CASCADE,
                token_hash  CHAR(64)     NOT NULL UNIQUE,
                expires_at  TIMESTAMPTZ  NOT NULL,
                revoked_at  TIMESTAMPTZ  NULL,
                created_at  TIMESTAMPTZ  NOT NULL DEFAULT now()
            )
            SQL);

        $this->addSql('CREATE INDEX idx_refresh_tokens_user_id ON refresh_tokens (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE refresh_tokens');
    }
}
