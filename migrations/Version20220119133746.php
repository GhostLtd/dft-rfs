<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220119133746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notification_interception table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE domestic_notification_interception (id INT AUTO_INCREMENT NOT NULL, address_line VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE domestic_notification_interception');
    }
}
