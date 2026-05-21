<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260526125107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CSRGT: Add quality_flag_count aggregate column on domestic_survey_response';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE domestic_survey_response ADD quality_flag_count INT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE domestic_survey_response DROP quality_flag_count
        SQL);
    }
}
