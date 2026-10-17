<?php

declare(strict_types=1);

namespace App\Migrations\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * TODO LISTS – global, note-like lists; the whole body is one sanitized HTML string (FR-TODO-*)
 */
final class Version20261017162056 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create todo_lists table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE todo_lists (
                id         BIGINT       GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                user_id    BIGINT       NOT NULL REFERENCES users (id) ON DELETE CASCADE,
                title      VARCHAR(100) NOT NULL CHECK (btrim(title) <> ''),
                content    TEXT         NOT NULL DEFAULT '' CHECK (octet_length(content) <= 102400),
                created_at TIMESTAMPTZ  NOT NULL DEFAULT now(),
                updated_at TIMESTAMPTZ  NOT NULL DEFAULT now()
            )
            SQL);

        // lists are shown most recently edited first
        $this->addSql('CREATE INDEX idx_todo_lists_user_updated ON todo_lists (user_id, updated_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE todo_lists');
    }
}
