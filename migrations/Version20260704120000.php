<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260704120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le statut aux réservations';
    }

    public function up(Schema $schema): void
    {
        // Colonne status pour savoir si la réservation est confirmée, etc.
        $this->addSql("ALTER TABLE reservation ADD COLUMN status VARCHAR(20) DEFAULT 'confirmed' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation DROP COLUMN status');
    }
}
