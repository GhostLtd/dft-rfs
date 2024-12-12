<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251104114813 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'IRHS exports view';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_export_international_survey AS ( 
SELECT 
    f.SurveyID
    , f.ReferenceNumber
    , f.SurveyStatus
    , f.SurveyPeriodStart
    , f.SurveyPeriodEnd
    , f.NumberOfReminders
    , f.NumberOfInternationalJourneys
    , f.ActivityStatus
    , f.BusinessNature
    , f.NumberOfEmployees
    , f.ReasonForEmptySurvey
    , f.ReasonForEmptySurveyOther
    , t.TripID
    , t.RegMark
    , t.OperationType
    , t.Origin
    , t.Destination

    , t.OutboundDate
    , t.OutboundUkPort
    , t.OutboundForeignPort
    , t.OutboundWasEmpty
    , t.OutboundWasLimitedBySpace
    , t.OutboundWasLimitedByWeight
     
    , t.ReturnDate
    , t.ReturnUkPort
    , t.ReturnForeignPort
    , t.ReturnWasEmpty
    , t.ReturnWasLimitedBySpace
    , t.ReturnWasLimitedByWeight
     
    , t.GrossWeight
    , t.CarryingCapacity
    , t.BodyType
    , t.AxleConfiguration
    , t.CountriesTransitedCodes
    , t.CountriesTransitedOther
     
    , c.ConsignmentID
    , c.ActionType
    , c.MOA
    , c.TypeOfGoods
    , c.TypeOfGoodsOther
    , c.DangerousGoodsCode
    , c.WeightOfGoodsCarried
    , c.OriginPlace
    , c.OriginCountryCode
    , c.OriginCountryOther
    , c.LoadedOrder
    , c.DestinationPlace
    , c.DestinationCountryCode
    , c.DestinationCountryOther
    , c.UnloadedOrder
    , c.RowNumber
FROM view_international_firms AS f
LEFT JOIN view_international_trips AS t ON t.SurveyID = f.SurveyID
LEFT JOIN view_international_consignments AS c ON c.TripID = t.TripID
ORDER BY f.SurveyPeriodStart DESC, t.SurveyID, t.TripID, c.RowNumber
);
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS view_export_international_survey');
    }
}
