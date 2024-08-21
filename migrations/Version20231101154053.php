<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231101154053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoRo: Remove QA flag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey DROP quality_assured');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_survey ADD quality_assured TINYINT(1) DEFAULT NULL');
    }
}
