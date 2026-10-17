<?php

declare(strict_types=1);

namespace App\Migrations\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * USERS – timezone, blocking, case-insensitive email
 */
final class Version20261017100756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add timezone and blocked_at to users, make email unique case-insensitively';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE users
                ADD COLUMN timezone   VARCHAR(64) NOT NULL DEFAULT 'Europe/Prague',
                ADD COLUMN blocked_at TIMESTAMPTZ NULL
            SQL);

        // FR-AUTH-01: "Test@x.cz" and "test@x.cz" are the same account
        $this->addSql('CREATE UNIQUE INDEX uniq_users_email_lower ON users (lower(email))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_users_email_lower');
        $this->addSql('ALTER TABLE users DROP COLUMN blocked_at, DROP COLUMN timezone');
    }
}
