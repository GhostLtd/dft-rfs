<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240516130206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Driver availability: Remove exported_date field';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_driver_availablity DROP exported_date');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_driver_availablity ADD exported_date DATETIME DEFAULT NULL');
    }
}
