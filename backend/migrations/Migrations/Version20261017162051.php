<?php

declare(strict_types=1);

namespace App\Migrations\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * SUBCATEGORIES – user-owned children of a category (FR-SUB-*)
 */
final class Version20261017162051 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create subcategories table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE subcategories (
                id          BIGINT       GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                user_id     BIGINT       NOT NULL REFERENCES users (id) ON DELETE CASCADE,
                category_id SMALLINT     NOT NULL REFERENCES categories (id),
                name        VARCHAR(100) NOT NULL CHECK (btrim(name) <> ''),
                color       CHAR(7)      NOT NULL CHECK (color ~ '^#[0-9A-Fa-f]{6}$'),
                created_at  TIMESTAMPTZ  NOT NULL DEFAULT now(),
                updated_at  TIMESTAMPTZ  NOT NULL DEFAULT now(),

                -- target of the composite FK from events: lets the DB guarantee that an event's
                -- subcategory belongs to the same user and the same category (FR-EVT-03)
                CONSTRAINT uniq_subcategories_id_user_category UNIQUE (id, user_id, category_id)
            )
            SQL);

        // FR-SUB-03: name unique per user within a category, case-insensitive; also serves lookups by user_id
        $this->addSql('CREATE UNIQUE INDEX uniq_subcategories_user_category_name ON subcategories (user_id, category_id, lower(name))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE subcategories');
    }
}
