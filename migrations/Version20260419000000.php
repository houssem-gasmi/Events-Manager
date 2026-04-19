<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add paid event pricing support.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events ADD price INTEGER NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE events DROP COLUMN price');
    }
}
