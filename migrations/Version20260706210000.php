<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260706210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Validation d\'inscription par e-mail (jeton + statut vérifié)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD is_email_verified BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE users ADD email_verification_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD email_verification_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9C1CC006B ON users (email_verification_token)');
        $this->addSql('UPDATE users SET is_email_verified = true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_1483A5E9C1CC006B');
        $this->addSql('ALTER TABLE users DROP is_email_verified');
        $this->addSql('ALTER TABLE users DROP email_verification_token');
        $this->addSql('ALTER TABLE users DROP email_verification_token_expires_at');
    }
}
