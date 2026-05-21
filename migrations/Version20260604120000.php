<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'CSRGT: Add quality_flags_seen and quality_flag_signature columns on domestic_survey_response';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE domestic_survey_response ADD quality_flags_seen TINYINT(1) DEFAULT NULL, ADD quality_flag_signature VARCHAR(32) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE domestic_survey_response DROP quality_flags_seen, DROP quality_flag_signature
        SQL);
    }
}
