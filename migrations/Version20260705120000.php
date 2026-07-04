<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260705120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prestation live chat à distance, lieu virtuel et messages de chat';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service ADD COLUMN is_remote_live_chat BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE location ADD COLUMN is_remote BOOLEAN DEFAULT false NOT NULL');

        $this->addSql("INSERT INTO location (address, country, city, is_global, is_remote) VALUES ('En ligne', 'France', 'À distance', false, true)");

        $this->addSql("INSERT INTO service (title, description, duration, price, is_global, is_remote_live_chat) VALUES (
            'Live chat avec masseur chat',
            'Prestation à distance : échangez en direct avec votre masseur chat. Ses réponses sont générées spontanément, comme s''il marchait sur le clavier !',
            '30 min',
            15,
            true,
            true
        )");

        $this->addSql('
            CREATE TABLE live_chat_message (
                id SERIAL PRIMARY KEY,
                reservation_id INT NOT NULL,
                sender VARCHAR(10) NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL
            )
        ');
        $this->addSql('ALTER TABLE live_chat_message ADD CONSTRAINT fk_live_chat_reservation FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_live_chat_reservation ON live_chat_message (reservation_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE live_chat_message DROP CONSTRAINT fk_live_chat_reservation');
        $this->addSql('DROP TABLE live_chat_message');
        $this->addSql('DELETE FROM service WHERE is_remote_live_chat = true');
        $this->addSql('DELETE FROM location WHERE is_remote = true');
        $this->addSql('ALTER TABLE location DROP COLUMN is_remote');
        $this->addSql('ALTER TABLE service DROP COLUMN is_remote_live_chat');
    }
}
