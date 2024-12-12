<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251029134657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'IRHS exports view - trips';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_international_trips AS (
    WITH trip_json_country_codes AS (
        -- Get codes from the country_code column
        SELECT
            t.id AS trip_id
            , TRIM(JSON_UNQUOTE(j.val)) AS country_code
            , NULL                      AS country_other
        FROM international_trip t
        JOIN JSON_TABLE(
            CASE
                -- 99.9% are like this ["FR","DE","BE"]
                WHEN JSON_TYPE(t.countries_transitted) = 'ARRAY' THEN t.countries_transitted
                -- However, I've also seen this: {"0":"FR","1":"DE","3":"BE"} 
                -- turn them into ["FR","DE","BE"]
                WHEN JSON_TYPE(t.countries_transitted) = 'OBJECT' THEN JSON_EXTRACT(t.countries_transitted, '$.*')
                ELSE JSON_ARRAY()
            END,
            '$[*]' COLUMNS (val VARCHAR(10) PATH '$')
        ) AS j
    )
    , trip_country_other_codes AS (
        -- Get country names from the country_code_other column,
        -- resolving them into country codes where possible
        
        -- ["SLOVENIA", "BANANA"] -> (trip_id, "SI"), (trip_id, "BANANA")
        SELECT
            t.id                                     AS trip_id
            , c.code                                 AS country_code
            , CASE
                WHEN c.code IS NULL THEN NULLIF(TRIM(j.country_name), '')
              END                                    AS country_other
        FROM international_trip t
        JOIN JSON_TABLE(
            -- Convert "Ireland, Czech Republic" → ["Ireland", "Czech Republic"]
            CONCAT('["', REPLACE(t.countries_transitted_other, ',', '","'), '"]'),
            '$[*]' COLUMNS (country_name VARCHAR(255) PATH '$')
        ) AS j ON
            t.countries_transitted_other IS NOT NULL
            AND t.countries_transitted_other <> ''
        LEFT JOIN view_countries c ON
            LOWER(TRIM(j.country_name)) = LOWER(TRIM(c.name))
    )
    , action_origin_country_codes AS (
        SELECT
            c.TripID                   AS trip_id
            , c.OriginCountryCode      AS country_code
            , NULL                     AS country_other
        FROM view_international_consignments c
        WHERE 
            c.OriginCountryCode IS NOT NULL
            AND (c.ActionType = 'consignment' OR c.OriginCountryCode != 'GB')
    )
    , action_destination_country_codes AS (
        SELECT
            c.TripID                   AS trip_id
            , c.DestinationCountryCode AS country_code
            , NULL                     AS country_other
        FROM view_international_consignments c
        WHERE 
            c.DestinationCountryCode IS NOT NULL
            AND (c.ActionType = 'consignment' OR c.DestinationCountryCode != 'GB')
    )
    , action_origin_country_others AS (
        SELECT
            c.TripID                       AS trip_id
            , NULL                         AS country_code
            , TRIM(c.OriginCountryOther)
                COLLATE utf8mb4_unicode_ci AS country_other
        FROM view_international_consignments c
        WHERE 
            c.OriginCountryOther IS NOT NULL
            AND TRIM(c.OriginCountryOther) != ''
    )
    , action_destination_country_others AS (
        SELECT
            c.TripID                          AS trip_id
            , NULL                            AS country_code
            , TRIM(c.DestinationCountryOther) 
                COLLATE utf8mb4_unicode_ci    AS country_other
        FROM view_international_consignments c
        WHERE 
            c.DestinationCountryOther IS NOT NULL
            AND TRIM(c.DestinationCountryOther) != ''
    )
    , action_countries_others_all AS (
        SELECT trip_id, country_code, country_other FROM action_origin_country_others
        UNION ALL
        SELECT trip_id, country_code, country_other FROM action_destination_country_others
    )
    , action_countries_others_with_resolved_names_removed AS (
        -- split "Austria, Slovenia" → rows; drop resolvable names
        SELECT
            a.trip_id
            , NULL AS country_code
            , TRIM(j.name) AS country_other
        FROM action_countries_others_all AS a
        JOIN JSON_TABLE(
            CONCAT('["', REGEXP_REPLACE(a.country_other,'[[:space:]]*,[[:space:]]*','","'), '"]'),
            '$[*]' COLUMNS (name VARCHAR(255) PATH '$')
        ) AS j
        LEFT JOIN view_countries vc
            ON LOWER(TRIM(j.name)) = LOWER(TRIM(vc.name))
        WHERE a.country_other IS NOT NULL
            AND TRIM(a.country_other) != ''
            AND vc.code IS NULL -- keep only truly unresolved
    )
    , transited_country_codes_and_others AS (
        -- Bring all of them into a big list
        SELECT trip_id, country_code, country_other
        FROM trip_json_country_codes
        UNION ALL
        SELECT trip_id, country_code, country_other
        FROM trip_country_other_codes
        UNION ALL
        SELECT trip_id, country_code, country_other
        FROM action_origin_country_codes
        UNION ALL
        SELECT trip_id, country_code, country_other
        FROM action_destination_country_codes
        UNION ALL
        SELECT trip_id, country_code, country_other
        FROM action_countries_others_with_resolved_names_removed
    )
    , transited_country_codes_deduplicated AS (
        -- Remove duplicates
        SELECT DISTINCT trip_id, country_code
        FROM transited_country_codes_and_others
        WHERE country_code IS NOT NULL AND country_code <> ''
    )
    , transited_country_others_deduplicated AS (
        SELECT DISTINCT trip_id, country_other
        FROM transited_country_codes_and_others
        WHERE country_other IS NOT NULL AND country_other <> ''
    )
    , transited_country_codes_aggregate AS (
        -- Order and convert to JSON
        -- (N.B. JSON_ARRAYAGG didn't work well for this - specifically the lack of ordering)
        SELECT trip_id,
            CAST(
                CONCAT(
                       '[',
                       GROUP_CONCAT(JSON_QUOTE(country_code) ORDER BY country_code SEPARATOR ','),
                       ']'
                ) AS JSON
           ) AS countries_transited
        FROM transited_country_codes_deduplicated
        GROUP BY trip_id
    )
    , transited_country_others_aggregate AS (
        SELECT trip_id,
        CAST(CONCAT(
            '[',
                GROUP_CONCAT(JSON_QUOTE(country_other) ORDER BY country_other SEPARATOR ','),
            ']'
        ) AS JSON) AS countries_transited_other
        FROM transited_country_others_deduplicated
        GROUP BY trip_id
    )
    SELECT 
        t.id                                                   AS TripID
        , v.id                                                 AS VehicleID
        , sr.survey_id                                         AS SurveyID
        , v.registration_mark                                  AS RegMark
        , t.outbound_date                                      AS OutboundDate
        , t.return_date                                        AS ReturnDate
        , v.gross_weight                                       AS GrossWeight
        , v.carrying_capacity                                  AS CarryingCapacity
        , v.operation_type                                     AS OperationType
        , COALESCE(t.body_type, v.body_type)                   AS BodyType
        , t.origin                                             AS Origin
        , t.destination                                        AS Destination
        , t.outbound_uk_port                                   AS OutboundUkPort
        , t.outbound_foreign_port                              AS OutboundForeignPort
        , t.outbound_was_empty                                 AS OutboundWasEmpty
        , t.return_uk_port                                     AS ReturnUkPort
        , t.return_foreign_port                                AS ReturnForeignPort
        , t.return_was_empty                                   AS ReturnWasEmpty
        , cc.countries_transited                               AS CountriesTransitedCodes
        , co.countries_transited_other                         AS CountriesTransitedOther
        , t.outbound_was_limited_by_space                      AS OutboundWasLimitedBySpace
        , t.outbound_was_limited_by_weight                     AS OutboundWasLimitedByWeight
        , t.return_was_limited_by_space                        AS ReturnWasLimitedBySpace
        , t.return_was_limited_by_weight                       AS ReturnWasLimitedByWeight
        , COALESCE(t.axle_configuration, v.axle_configuration) AS AxleConfiguration
    FROM international_trip AS t
    JOIN international_vehicle v ON t.vehicle_id = v.id
    JOIN international_survey_response AS sr ON v.survey_response_id = sr.id
    JOIN international_survey AS s ON sr.survey_id = s.id
    LEFT JOIN transited_country_codes_aggregate AS cc ON cc.trip_id = t.id
    LEFT JOIN transited_country_others_aggregate AS co ON co.trip_id = t.id
)
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS view_international_trips');
    }
}
