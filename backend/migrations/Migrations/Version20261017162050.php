<?php

declare(strict_types=1);

namespace App\Migrations\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * CATEGORIES – fixed, system-wide (FR-CAT-01). Rows are inserted by CategorySeeder.
 */
final class Version20261017162050 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create categories table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE categories (
                id       SMALLINT     GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
                slug     VARCHAR(64)  NOT NULL UNIQUE,
                name     VARCHAR(100) NOT NULL,
                position SMALLINT     NOT NULL UNIQUE
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE categories');
    }
}
