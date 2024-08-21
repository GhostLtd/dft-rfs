<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230802085516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoRo: add data_entry_method column to Survey';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey ADD data_entry_method VARCHAR(10) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey DROP data_entry_method');
    }
}
