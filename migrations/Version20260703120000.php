<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permet à un chat d\'être rattaché à plusieurs salons et à un compte utilisateur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE cat_location (
                cat_id INT NOT NULL,
                location_id INT NOT NULL,
                PRIMARY KEY (cat_id, location_id)
            )
        ');
        $this->addSql('ALTER TABLE cat_location ADD CONSTRAINT fk_cat_location_cat FOREIGN KEY (cat_id) REFERENCES cat (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE cat_location ADD CONSTRAINT fk_cat_location_location FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_cat_location_location ON cat_location (location_id)');

        $this->addSql('
            INSERT INTO cat_location (cat_id, location_id)
            SELECT id, location_id FROM cat WHERE location_id IS NOT NULL
        ');

        $this->addSql('ALTER TABLE cat DROP CONSTRAINT fk_cat_location');
        $this->addSql('ALTER TABLE cat DROP COLUMN location_id');

        $this->addSql('ALTER TABLE cat ADD COLUMN user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cat ADD CONSTRAINT fk_cat_user FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_cat_user ON cat (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cat ADD COLUMN location_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cat ADD CONSTRAINT fk_cat_location FOREIGN KEY (location_id) REFERENCES location (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('
            UPDATE cat c
            SET location_id = cl.location_id
            FROM (
                SELECT DISTINCT ON (cat_id) cat_id, location_id
                FROM cat_location
                ORDER BY cat_id, location_id
            ) cl
            WHERE c.id = cl.cat_id
        ');

        $this->addSql('ALTER TABLE cat DROP CONSTRAINT fk_cat_user');
        $this->addSql('DROP INDEX uniq_cat_user');
        $this->addSql('ALTER TABLE cat DROP COLUMN user_id');

        $this->addSql('ALTER TABLE cat_location DROP CONSTRAINT fk_cat_location_cat');
        $this->addSql('ALTER TABLE cat_location DROP CONSTRAINT fk_cat_location_location');
        $this->addSql('DROP TABLE cat_location');
    }
}
