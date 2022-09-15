<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220804111502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add borderCrossed field to domestic day stop + summary';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_day_stop ADD border_crossed TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE domestic_day_summary ADD border_crossed TINYINT(1) DEFAULT NULL');

        $this->addSql($this->getUpdateQuery('domestic_day_stop'));
        $this->addSql($this->getUpdateQuery('domestic_day_summary'));
    }

    public function getUpdateQuery(string $table): string
    {
        return <<<EOQ
UPDATE ${table} s
LEFT JOIN domestic_day d ON s.day_id = d.id
LEFT JOIN domestic_survey_response r ON d.response_id = r.id
LEFT JOIN domestic_survey sv ON r.survey_id = sv.id
SET s.border_crossed = CASE
    WHEN s.border_crossing_location IS NOT NULL THEN 1
    ELSE 0
END
WHERE sv.is_northern_ireland = 1
EOQ;
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_day_stop DROP border_crossed');
        $this->addSql('ALTER TABLE domestic_day_summary DROP border_crossed');
    }
}
