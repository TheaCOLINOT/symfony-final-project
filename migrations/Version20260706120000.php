<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260706120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise les e-mails et ajoute une contrainte d\'unicité';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE users SET email = LOWER(TRIM(email)) WHERE email IS NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_users_email ON users (email)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_users_email');
    }
}
