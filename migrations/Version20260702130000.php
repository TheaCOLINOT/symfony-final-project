<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260702130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permet à plusieurs managers d\'être rattachés à une même localisation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE manager ADD COLUMN location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE manager ADD CONSTRAINT fk_manager_location FOREIGN KEY (location_id) REFERENCES location(id)');

        $this->addSql('
            UPDATE manager m
            SET location_id = l.id
            FROM location l
            WHERE l.manager_id = m.id
        ');

        $this->addSql('ALTER TABLE location DROP CONSTRAINT fk_location_manager');
        $this->addSql('ALTER TABLE location DROP COLUMN manager_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD COLUMN manager_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT fk_location_manager FOREIGN KEY (manager_id) REFERENCES manager(id)');

        $this->addSql('
            UPDATE location l
            SET manager_id = m.id
            FROM manager m
            WHERE m.location_id = l.id
        ');

        $this->addSql('ALTER TABLE manager DROP CONSTRAINT fk_manager_location');
        $this->addSql('ALTER TABLE manager DROP COLUMN location_id');
    }
}
