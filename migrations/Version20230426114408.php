<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230426114408 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoRo: Add QA support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey ADD quality_assured TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey DROP quality_assured');
    }
}
