<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230809131008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoRo: Add comments field to Survey';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey ADD comments LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey DROP comments');
    }
}
