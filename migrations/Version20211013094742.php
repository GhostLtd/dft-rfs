<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\International\SurveyResponse;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211013094742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'IRHS: Add missing "still-active" activity_status for companies with zero estimated international journeys';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<EOQ
UPDATE international_survey_response 
SET 
    activity_status=:newActivityStatus 
WHERE 
    activity_status IS NULL 
AND annual_international_journey_count=0 
AND number_of_employees IS NOT NULL
AND business_nature IS NOT NULL
EOQ, ['newActivityStatus' => SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE]);
    }

    public function down(Schema $schema): void
    {
    }
}
