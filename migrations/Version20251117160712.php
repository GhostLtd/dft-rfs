<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251117160712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RORO export views';
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->getRoroSurveyView());
        $this->addSql($this->getRoroVehicleCountView());
        $this->addSql($this->getRoroExportSurveyView());
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS view_export_roro_survey');
        $this->addSql('DROP VIEW IF EXISTS view_roro_survey');
        $this->addSql('DROP VIEW IF EXISTS view_roro_vehicle_count');
    }

    protected function getRoroVehicleCountView(): string
    {
        return <<< SQL
CREATE OR REPLACE VIEW view_roro_vehicle_count AS
SELECT
    vc.survey_id as SurveyID
    , if(vc.country_code is null, vc.other_code, vc.country_code) as CountryCode
    , COALESCE(vc.vehicle_count, 0) as VehicleCount
FROM roro_vehicle_count vc
JOIN roro_survey rs ON rs.id = vc.survey_id
WHERE rs.is_active_for_period = 1
SQL;
    }

    protected function getRoroSurveyView(): string
    {
        return <<< SQL
CREATE OR REPLACE VIEW view_roro_survey AS
SELECT
    rs.id AS SurveyID
    , YEAR(rs.survey_period_start) AS Year
    , CEIL(MONTH(rs.survey_period_start) / 3) AS Quarter
    , MONTH(rs.survey_period_start) AS Month
    , CONCAT(LPAD(rup.code, 2, '0'), LPAD(rfp.code, 2, '0'), LPAD(ro.code, 2, '0')) AS RoRoNo
    , LPAD(ro.code, 2, '0') AS OperatorCode
    , rs.is_active_for_period AS IsActiveForMonth
    , rs.state AS SurveyState
    , CASE 
        WHEN rs.is_active_for_period = 1 THEN 
            COALESCE(SUM(IF(rvc.other_code IS NULL OR rvc.other_code <> 'trailers', rvc.vehicle_count, NULL)), 0)
    END AS TotalPoweredVehicles
    , CASE 
        WHEN rs.is_active_for_period = 1 THEN COALESCE(SUM(rvc.vehicle_count), 0)
    END AS TotalVehicles
    , LPAD(rup.code, 2, '0') AS UKPortCode
    , LPAD(rfp.code, 2, '0') AS ForeignPortCode
    , rs.comments AS Comments
    , IF(rs.data_entry_method = 'advanced', 'bulk', rs.data_entry_method) AS DataEntryMethod
FROM roro_survey rs
    JOIN roro_operator ro ON rs.operator_id = ro.id
    JOIN route r ON r.id = rs.route_id
    JOIN route_foreign_port rfp ON rfp.id = r.foreign_port_id
    JOIN route_uk_port rup ON rup.id = r.uk_port_id
    LEFT JOIN roro_vehicle_count rvc ON rs.id = rvc.survey_id
WHERE rs.state IN ('closed', 'approved')
GROUP BY rs.id, rs.survey_period_start, rs.survey_period_start, rs.survey_period_start, rup.code, rfp.code, ro.code, ro.code, rs.is_active_for_period, rs.state, rup.code, rfp.code, rs.comments, rs.state, rs.data_entry_method, rs.data_entry_method
ORDER BY rs.survey_period_start, ro.code
SQL;
    }

    protected function getRoroExportSurveyView(): string
    {
        return <<<SQL
CREATE OR REPLACE VIEW view_export_roro_survey AS
SELECT 
    s.*
    , vc.AL
    , vc.AT
    , vc.BA
    , vc.BE
    , vc.BG
    , vc.`BY`
    , vc.CH
    , vc.CY
    , vc.CZ
    , vc.DE
    , vc.DK
    , vc.EE
    , vc.ES
    , vc.FI
    , vc.FR
    , vc.GB
    , vc.GE
    , vc.GR
    , vc.HR
    , vc.HU
    , vc.IE
    , vc.`IS`
    , vc.IT
    , vc.LT
    , vc.LU
    , vc.LV
    , vc.MD
    , vc.ME
    , vc.MK
    , vc.MT
    , vc.NL
    , vc.NO
    , vc.PL
    , vc.PT
    , vc.RO
    , vc.RS
    , vc.RU
    , vc.SE
    , vc.SI
    , vc.SK
    , vc.TR
    , vc.UA
    , vc.XK
    , vc.OtherCountries
    , vc.Unknown
    , vc.UnaccompaniedTrailers
FROM view_roro_survey AS s
LEFT JOIN (
    SELECT 
        SurveyID
        , SUM(IF(CountryCode = 'AL',      VehicleCount, 0)) AS AL
        , SUM(IF(CountryCode = 'AT',      VehicleCount, 0)) AS AT
        , SUM(IF(CountryCode = 'BA',      VehicleCount, 0)) AS BA
        , SUM(IF(CountryCode = 'BE',      VehicleCount, 0)) AS BE
        , SUM(IF(CountryCode = 'BG',      VehicleCount, 0)) AS BG
        , SUM(IF(CountryCode = 'BY',      VehicleCount, 0)) AS `BY`
        , SUM(IF(CountryCode = 'CH',      VehicleCount, 0)) AS CH
        , SUM(IF(CountryCode = 'CY',      VehicleCount, 0)) AS CY
        , SUM(IF(CountryCode = 'CZ',      VehicleCount, 0)) AS CZ
        , SUM(IF(CountryCode = 'DE',      VehicleCount, 0)) AS DE
        , SUM(IF(CountryCode = 'DK',      VehicleCount, 0)) AS DK
        , SUM(IF(CountryCode = 'EE',      VehicleCount, 0)) AS EE
        , SUM(IF(CountryCode = 'ES',      VehicleCount, 0)) AS ES
        , SUM(IF(CountryCode = 'FI',      VehicleCount, 0)) AS FI
        , SUM(IF(CountryCode = 'FR',      VehicleCount, 0)) AS FR
        , SUM(IF(CountryCode = 'GB',      VehicleCount, 0)) AS GB
        , SUM(IF(CountryCode = 'GE',      VehicleCount, 0)) AS GE
        , SUM(IF(CountryCode = 'GR',      VehicleCount, 0)) AS GR
        , SUM(IF(CountryCode = 'HR',      VehicleCount, 0)) AS HR
        , SUM(IF(CountryCode = 'HU',      VehicleCount, 0)) AS HU
        , SUM(IF(CountryCode = 'IE',      VehicleCount, 0)) AS IE
        , SUM(IF(CountryCode = 'IS',      VehicleCount, 0)) AS `IS`
        , SUM(IF(CountryCode = 'IT',      VehicleCount, 0)) AS IT
        , SUM(IF(CountryCode = 'LT',      VehicleCount, 0)) AS LT
        , SUM(IF(CountryCode = 'LU',      VehicleCount, 0)) AS LU
        , SUM(IF(CountryCode = 'LV',      VehicleCount, 0)) AS LV
        , SUM(IF(CountryCode = 'MD',      VehicleCount, 0)) AS MD
        , SUM(IF(CountryCode = 'ME',      VehicleCount, 0)) AS ME
        , SUM(IF(CountryCode = 'MK',      VehicleCount, 0)) AS MK
        , SUM(IF(CountryCode = 'MT',      VehicleCount, 0)) AS MT
        , SUM(IF(CountryCode = 'NL',      VehicleCount, 0)) AS NL
        , SUM(IF(CountryCode = 'NO',      VehicleCount, 0)) AS NO
        , SUM(IF(CountryCode = 'PL',      VehicleCount, 0)) AS PL
        , SUM(IF(CountryCode = 'PT',      VehicleCount, 0)) AS PT
        , SUM(IF(CountryCode = 'RO',      VehicleCount, 0)) AS RO
        , SUM(IF(CountryCode = 'RS',      VehicleCount, 0)) AS RS
        , SUM(IF(CountryCode = 'RU',      VehicleCount, 0)) AS RU
        , SUM(IF(CountryCode = 'SE',      VehicleCount, 0)) AS SE
        , SUM(IF(CountryCode = 'SI',      VehicleCount, 0)) AS SI
        , SUM(IF(CountryCode = 'SK',      VehicleCount, 0)) AS SK
        , SUM(IF(CountryCode = 'TR',      VehicleCount, 0)) AS TR
        , SUM(IF(CountryCode = 'UA',      VehicleCount, 0)) AS UA
        , SUM(IF(CountryCode = 'XK',      VehicleCount, 0)) AS XK
        , SUM(IF(CountryCode = 'other',   VehicleCount, 0)) AS OtherCountries
        , SUM(IF(CountryCode = 'unknown', VehicleCount, 0)) AS Unknown
        , SUM(IF(CountryCode = 'trailers',VehicleCount, 0)) AS UnaccompaniedTrailers
    FROM view_roro_vehicle_count
    GROUP BY SurveyID
) AS vc ON vc.SurveyID = s.SurveyID 
SQL;
    }
}
