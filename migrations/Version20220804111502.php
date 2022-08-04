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
        $this->addSql('ALTER TABLE domestic_day_stop ADD border_crossed TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE domestic_day_summary ADD border_crossed TINYINT(1) NOT NULL');

        $this->addSql(<<<EOQ
UPDATE domestic_day_stop 
SET border_crossed = CASE
    WHEN border_crossing_location IS NOT NULL THEN 1
    ELSE 0
END
EOQ);

        $this->addSql(<<<EOQ
UPDATE domestic_day_summary 
SET border_crossed = CASE
    WHEN border_crossing_location IS NOT NULL THEN 1
    ELSE 0
END
EOQ);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_day_stop DROP border_crossed');
        $this->addSql('ALTER TABLE domestic_day_summary DROP border_crossed');
    }
}
