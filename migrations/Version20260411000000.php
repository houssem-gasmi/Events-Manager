<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260411000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema for event management application.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE categories (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_categories_name ON categories (name)');
        $this->addSql('CREATE TABLE locations (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(120) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX uniq_locations_name ON locations (name)');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)\n+)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON users (email)');
        $this->addSql('CREATE TABLE events (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, category_id INTEGER DEFAULT NULL, organizer_id INTEGER DEFAULT NULL, title VARCHAR(180) NOT NULL, description CLOB NOT NULL, event_date DATETIME NOT NULL --(DC2Type:datetime_immutable)\n+, location VARCHAR(180) NOT NULL, participant_limit INTEGER NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)\n+, CONSTRAINT FK_5387574A12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5387574A5F37A13B FOREIGN KEY (organizer_id) REFERENCES users (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5387574A12469DE2 ON events (category_id)');
        $this->addSql('CREATE INDEX IDX_5387574A5F37A13B ON events (organizer_id)');
        $this->addSql('CREATE TABLE event_participants (event_id INTEGER NOT NULL, user_id INTEGER NOT NULL, PRIMARY KEY(event_id, user_id), CONSTRAINT FK_B1A77B298C74F53F FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B1A77B29A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B1A77B298C74F53F ON event_participants (event_id)');
        $this->addSql('CREATE INDEX IDX_B1A77B29A76ED395 ON event_participants (user_id)');
        $this->addSql("INSERT INTO categories (name) VALUES ('Conference')");
        $this->addSql("INSERT INTO categories (name) VALUES ('Workshop')");
        $this->addSql("INSERT INTO categories (name) VALUES ('Concert')");
        $this->addSql("INSERT INTO categories (name) VALUES ('Meetup')");
        $this->addSql("INSERT INTO categories (name) VALUES ('Seminar')");
        $this->addSql("INSERT INTO categories (name) VALUES ('Webinar')");

        $locations = [
            'Ariana',
            'Béja',
            'Ben Arous',
            'Bizerte',
            'Gabès',
            'Gafsa',
            'Jendouba',
            'Kairouan',
            'Kasserine',
            'Kébili',
            'Le Kef',
            'Mahdia',
            'Manouba',
            'Médenine',
            'Monastir',
            'Nabeul',
            'Sfax',
            'Sidi Bouzid',
            'Siliana',
            'Sousse',
            'Tataouine',
            'Tozeur',
            'Tunis',
            'Zaghouan',
        ];

        foreach ($locations as $location) {
            $this->addSql(sprintf("INSERT INTO locations (name) VALUES ('%s')", str_replace("'", "''", $location)));
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event_participants');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE locations');
        $this->addSql('DROP TABLE categories');
    }
}
