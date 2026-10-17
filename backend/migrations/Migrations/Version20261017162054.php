<?php

declare(strict_types=1);

namespace App\Migrations\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * DAY NOTES – one note per user and date (FR-DAY-*)
 */
final class Version20261017162054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create day_notes table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE day_notes (
                id         BIGINT      GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                user_id    BIGINT      NOT NULL REFERENCES users (id) ON DELETE CASCADE,
                date       DATE        NOT NULL,
                -- an empty note is never stored – saving an empty note deletes the row (FR-DAY-02)
                content    TEXT        NOT NULL CHECK (btrim(content) <> '' AND char_length(content) <= 5000),
                created_at TIMESTAMPTZ NOT NULL DEFAULT now(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),

                -- also the index for week lookups (user_id, date BETWEEN monday AND sunday)
                CONSTRAINT uniq_day_notes_user_date UNIQUE (user_id, date)
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE day_notes');
    }
}
