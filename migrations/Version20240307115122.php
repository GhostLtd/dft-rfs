<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240307115122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates to session table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data LONGBLOB NOT NULL');
        $this->addSql('CREATE INDEX sess_lifetime_idx ON sessions (sess_lifetime)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX sess_lifetime_idx ON sessions');
        $this->addSql('ALTER TABLE sessions CHANGE sess_data sess_data MEDIUMBLOB NOT NULL');
    }
}
