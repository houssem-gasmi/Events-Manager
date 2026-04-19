<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add event tickets for paid registrations.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE event_tickets (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, event_id INTEGER NOT NULL, user_id INTEGER NOT NULL, stripe_session_id VARCHAR(255) NOT NULL, ticket_code VARCHAR(32) NOT NULL, confirmation_sent_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)\n+, reminder_sent_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)\n+, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)\n+, CONSTRAINT FK_8F9A5A898C74F53F FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8F9A5A89A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX uniq_event_ticket_session ON event_tickets (stripe_session_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_event_ticket_user_event ON event_tickets (event_id, user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_event_ticket_code ON event_tickets (ticket_code)');
        $this->addSql('CREATE INDEX IDX_8F9A5A898C74F53F ON event_tickets (event_id)');
        $this->addSql('CREATE INDEX IDX_8F9A5A89A76ED395 ON event_tickets (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event_tickets');
    }
}
