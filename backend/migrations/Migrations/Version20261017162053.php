<?php

declare(strict_types=1);

namespace App\Migrations\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * EVENTS – timetable entries (FR-EVT-*)
 */
final class Version20261017162053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create events table';
    }
    //                -- first constraint:
    //                -- FR-EVT-02: positive duration, max. 24 hours

    //                -- FR-EVT-03 + FR-SUB-06: the subcategory must be the user's own and in the same category,
    //                -- and it cannot be deleted while events reference it. NO ACTION (not RESTRICT) on purpose:
    //                -- it is checked at the end of the statement, so deleting a user still cascades cleanly
    //                -- through both subcategories and events. NULL subcategory_id skips the check (MATCH SIMPLE).
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE events (
                id             BIGINT       GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                user_id        BIGINT       NOT NULL REFERENCES users (id) ON DELETE CASCADE,
                category_id    SMALLINT     NOT NULL REFERENCES categories (id),
                subcategory_id BIGINT       NULL,
                title          VARCHAR(150) NOT NULL CHECK (btrim(title) <> ''),
                description    TEXT         NULL CHECK (char_length(description) <= 5000),
                start_at       TIMESTAMPTZ  NOT NULL,
                end_at         TIMESTAMPTZ  NOT NULL,
                status         VARCHAR(20)  NOT NULL CHECK (status IN ('planned', 'done', 'skipped')),
                created_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),
                updated_at     TIMESTAMPTZ  NOT NULL DEFAULT now(),

                CONSTRAINT chk_events_time_range CHECK (end_at > start_at AND end_at - start_at <= INTERVAL '24 hours'),

                CONSTRAINT fk_events_subcategory FOREIGN KEY (subcategory_id, user_id, category_id)
                    REFERENCES subcategories (id, user_id, category_id)
            )
            SQL);

        // week view, event lists and statistics all filter by user and time range (NFR-08)
        $this->addSql('CREATE INDEX idx_events_user_start ON events (user_id, start_at)');
        // FK lookups: "does this subcategory still have events?" (FR-SUB-06)
        $this->addSql('CREATE INDEX idx_events_subcategory ON events (subcategory_id) WHERE subcategory_id IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE events');
    }
}
