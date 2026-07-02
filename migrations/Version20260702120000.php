<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260702120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Lie les managers aux utilisateurs, ajoute la localisation globale et rattache les chats aux salons';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE manager ADD COLUMN user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE manager ADD CONSTRAINT fk_manager_user FOREIGN KEY (user_id) REFERENCES users(id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_manager_user ON manager (user_id)');

        $this->addSql('ALTER TABLE location ADD COLUMN is_global BOOLEAN DEFAULT FALSE NOT NULL');

        $this->addSql('ALTER TABLE cat ADD COLUMN location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cat ADD CONSTRAINT fk_cat_location FOREIGN KEY (location_id) REFERENCES location(id)');

        $this->addSql('ALTER TABLE service ADD COLUMN is_global BOOLEAN DEFAULT TRUE NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service DROP COLUMN is_global');
        $this->addSql('ALTER TABLE cat DROP CONSTRAINT fk_cat_location');
        $this->addSql('ALTER TABLE cat DROP COLUMN location_id');
        $this->addSql('ALTER TABLE location DROP COLUMN is_global');
        $this->addSql('ALTER TABLE manager DROP CONSTRAINT fk_manager_user');
        $this->addSql('DROP INDEX uniq_manager_user');
        $this->addSql('ALTER TABLE manager DROP COLUMN user_id');
    }
}
