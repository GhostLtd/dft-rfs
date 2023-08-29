<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230829105533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoRo: data_entry_method should be nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey CHANGE data_entry_method data_entry_method VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey CHANGE data_entry_method data_entry_method VARCHAR(10) NOT NULL');
    }
}
