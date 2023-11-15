<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231117104748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoRo: OperatorGroup schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE roro_operator_group (id CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE roro_operator_group');
    }
}
