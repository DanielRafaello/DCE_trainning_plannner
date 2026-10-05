<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * USERS
 */
final class Version20261004204140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE users (
                id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                email         VARCHAR(255) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                name          VARCHAR(100) NOT NULL,
                role          VARCHAR(20)  NOT NULL CHECK (role IN ('user', 'admin')),
                created_at    TIMESTAMPTZ  NOT NULL DEFAULT now(),
                updated_at    TIMESTAMPTZ  NOT NULL DEFAULT now()
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE users');
    }
}
