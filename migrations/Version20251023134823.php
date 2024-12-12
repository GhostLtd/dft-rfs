<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251023134823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'IRHS exports view - consignments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP FUNCTION IF EXISTS irhs_empty_uuid');

        // The PHP generates UUIDs on the fly for empty consignments, and this function replicates that in MySQL
        $this->addSql(<<<SQL
CREATE FUNCTION irhs_empty_uuid(seed VARCHAR(128), ctx VARCHAR(32))
RETURNS CHAR(36)
DETERMINISTIC
BEGIN
  -- 16-byte binary from MD5
  DECLARE d VARBINARY(16);
  DECLARE b7 INT;
  DECLARE b9 INT;

  SET d  = UNHEX(MD5(CONCAT(ctx, seed)));

  -- Bytes are 1-indexed in SUBSTR; PHP used data[6] and data[8] (0-indexed)
  -- so here we modify positions 7 and 9.
  SET b7 = (ORD(SUBSTR(d, 7, 1)) & 15) | 64;   -- version 4
  SET b9 = (ORD(SUBSTR(d, 9, 1)) & 63) | 128;  -- variant 10

  -- Rebuild d with modified bytes 7 and 9
  SET d = CONCAT(
    SUBSTR(d, 1, 6),
    CHAR(b7),
    SUBSTR(d, 8, 1),
    CHAR(b9),
    SUBSTR(d,10)   -- from byte 10 to end
  );

  -- Format as 8-4-4-4-12 hex with hyphens
  RETURN LOWER(CONCAT(
    SUBSTR(HEX(d),  1, 8), '-',  -- 4 bytes
    SUBSTR(HEX(d),  9, 4), '-',  -- 2 bytes
    SUBSTR(HEX(d), 13, 4), '-',  -- 2 bytes
    SUBSTR(HEX(d), 17, 4), '-',  -- 2 bytes
    SUBSTR(HEX(d), 21, 12)       -- 6 bytes
  ));
END
SQL);
        $this->addSql(<<<SQL
CREATE OR REPLACE VIEW view_international_consignments AS ( 
    WITH first_and_last_actions AS (
        SELECT
            a.trip_id
            , SUBSTRING_INDEX(
                GROUP_CONCAT(a.id ORDER BY a.number ASC), ',', 1
            ) AS first_action_id
            , SUBSTRING_INDEX(
                GROUP_CONCAT(a.id ORDER BY a.number DESC), ',', 1
            ) AS last_action_id
        FROM international_action a
        GROUP BY a.trip_id
    ),
    
    first_and_last_actions_details AS (
        -- Merge in details for the first and last actions
        SELECT
            fl.trip_id
            , fa.id                                                        AS first_id
            -- If country is '0', this indicates the "other"
            , NULLIF(fa.country, '0')                                      AS first_country_code_raw
            , fa.country_other                                             AS first_country_other_raw
            -- So the first of:
            --   fa.country (confusingly a code), if a non-other option is selected
            --   cf.code, if available (this is where the country_other field matches a country name)
            , COALESCE(NULLIF(fa.country,'0'), cf.code)                    AS first_country_code
            , CASE
                WHEN COALESCE(NULLIF(fa.country,'0'), cf.code) IS NULL
                THEN NULLIF(TRIM(fa.country_other), '')
              END                                                          AS first_country_other_unresolved
            , fa.name                                                      AS first_name
            , fa.number                                                    AS first_number

            , la.id                                                        AS last_id
            , NULLIF(la.country, '0')                                      AS last_country_code_raw
            , la.country_other                                             AS last_country_other_raw
            , COALESCE(NULLIF(la.country,'0'), cl.code)                    AS last_country_code
            , CASE
                WHEN COALESCE(NULLIF(la.country,'0'), cl.code) IS NULL
                THEN NULLIF(TRIM(la.country_other), '')
              END                                                          AS last_country_other_unresolved
            , la.name                                                      AS last_name
            , la.number                                                    AS last_number
        FROM first_and_last_actions fl
        JOIN international_action fa ON fa.id = fl.first_action_id
        JOIN international_action la ON la.id = fl.last_action_id
        LEFT JOIN view_countries cf ON fa.country = '0' AND fa.country_other = cf.name
        LEFT JOIN view_countries cl ON la.country = '0' AND la.country_other = cl.name
    ),
    trip_flags AS (
        -- Add extra flags, now we've resolved country codes
        SELECT
            fld.trip_id
            , (fld.first_country_code IS NULL OR fld.first_country_code <> 'GB') AS has_outbound_empty
            , (fld.last_country_code IS NULL OR fld.last_country_code  <> 'GB') AS has_return_empty
            , CASE WHEN (fld.first_country_code IS NULL OR fld.first_country_code <> 'GB') THEN 2 ELSE 0 END AS number_offset
        FROM first_and_last_actions_details fld
    ),
    outbound_rows AS (
      -- Generate the missing outbound journey when the first country is not GB...
      SELECT
        0                                    AS step_order
        , 'outbound-empty'                   AS ActionType
        , irhs_empty_uuid(
            CAST(t.id AS CHAR), 
            'outbound' 
          ) COLLATE utf8mb4_unicode_ci       AS ConsignmentID
        , t.id                               AS TripID
        , 'NS'                               AS MOA
        , 'empty'                            AS TypeOfGoods
        , NULL                               AS TypeOfGoodsOther
        , NULL                               AS DangerousGoodsCode
        , 0                                  AS WeightOfGoodsCarried
        , t.origin                           AS OriginPlace
        , 'GB'                               AS OriginCountryCode
        , NULL                               AS OriginCountryOther
        , 1                                  AS LoadedOrder
        , fld.first_name                     AS DestinationPlace
        , fld.first_country_code             AS DestinationCountryCode
        , fld.first_country_other_unresolved AS DestinationCountryOther
        , 2                                  AS UnloadedOrder
      FROM trip_flags tf
      JOIN international_trip t ON t.id = tf.trip_id
      JOIN first_and_last_actions_details fld ON fld.trip_id = tf.trip_id
      WHERE tf.has_outbound_empty = 1
    ),
    
    paired_loadings AS (
      SELECT
        u.number                                                      AS step_order
        , 'consignment'                                               AS ActionType
        , u.id                                                        AS ConsignmentID
        , t.id                                                        AS TripID
        , l.cargo_type_code                                           AS MOA
        , l.goods_description                                         AS TypeOfGoods
        , l.goods_description_other                                   AS TypeOfGoodsOther
        , CASE 
            WHEN l.hazardous_goods_code = '0' THEN NULL 
            ELSE l.hazardous_goods_code 
        END                                                           AS DangerousGoodsCode
        , CASE 
            WHEN u.weight_unloaded_all = 1 THEN l.weight_of_goods 
            ELSE u.weight_of_goods
        END                                                           AS WeightOfGoodsCarried
        , l.name                                                      AS OriginPlace
        , COALESCE(NULLIF(l.country,'0'), cl.code)                    AS OriginCountryCode
        , CASE
            WHEN COALESCE(NULLIF(l.country,'0'), cl.code) IS NULL
            THEN NULLIF(TRIM(l.country_other), '')
          END                                                         AS OriginCountryOther
        , (l.number + tf.number_offset)                               AS LoadedOrder
        , u.name                                                      AS DestinationPlace
        , COALESCE(NULLIF(u.country,'0'), cu.code)                    AS DestinationCountryCode
        , CASE
            WHEN COALESCE(NULLIF(u.country,'0'), cu.code) IS NULL
            THEN NULLIF(TRIM(u.country_other), '')
          END                                                         AS DestinationCountryOther
        , (u.number + tf.number_offset)                               AS UnloadedOrder
      FROM international_action u
      JOIN international_action l ON l.id = u.loading_action_id
      JOIN international_trip t ON t.id = u.trip_id
      JOIN trip_flags tf ON tf.trip_id = u.trip_id
      LEFT JOIN view_countries cl ON l.country = '0' AND l.country_other = cl.name
      LEFT JOIN view_countries cu ON u.country = '0' AND u.country_other = cu.name
    ),
    lone_loadings AS (
      -- Anything with no unloads
      SELECT
        l.number                                                                           AS step_order
        , 'consignment'                                                                    AS ActionType
        , l.id                                                                             AS ConsignmentID
        , t.id                                                                             AS TripID
        , l.cargo_type_code                                                                AS MOA
        , l.goods_description                                                              AS TypeOfGoods
        , l.goods_description_other                                                        AS TypeOfGoodsOther
        , CASE WHEN l.hazardous_goods_code = '0' THEN NULL ELSE l.hazardous_goods_code END AS DangerousGoodsCode
        , l.weight_of_goods                                                                AS WeightOfGoodsCarried
        , l.name                                                                           AS OriginPlace
        , COALESCE(NULLIF(l.country,'0'), cl.code)                                         AS OriginCountryCode
        , CASE
            WHEN COALESCE(NULLIF(l.country,'0'), cl.code) IS NULL
            THEN NULLIF(TRIM(l.country_other), '')
          END                                                                              AS OriginCountryOther
        , (l.number + tf.number_offset)                                                    AS LoadedOrder
        , NULL                                                                             AS DestinationPlace
        , NULL                                                                             AS DestinationCountryCode
        , NULL                                                                             AS DestinationCountryOther
        , NULL                                                                             AS UnloadedOrder
      FROM international_action l
      LEFT JOIN international_action u ON u.loading_action_id = l.id
      JOIN international_trip t ON t.id = l.trip_id
      JOIN trip_flags tf ON tf.trip_id = l.trip_id
      LEFT JOIN view_countries cl ON l.country = '0' AND l.country_other = cl.name
      WHERE l.loading = 1 AND u.id IS NULL
    ),
    
    return_rows AS (
      -- Add missing empty return journeys (i.e. when the last_country is not GB) 
      SELECT
        2147483647                                                                 AS step_order
        , 'return-empty'                                                           AS ActionType
        , irhs_empty_uuid(CAST(t.id AS CHAR), 'return') COLLATE utf8mb4_unicode_ci AS ConsignmentID
        , t.id                                                                     AS TripID
        , 'NS'                                                                     AS MOA
        , 'empty'                                                                  AS TypeOfGoods
        , NULL                                                                     AS TypeOfGoodsOther
        , NULL                                                                     AS DangerousGoodsCode
        , 0                                                                        AS WeightOfGoodsCarried
        , fld.last_name                                                            AS OriginPlace
        , fld.last_country_code                                                    AS OriginCountryCode
        , fld.last_country_other_unresolved                                        AS OriginCountryOther
        , (fld.last_number + tf.number_offset + 1)                                 AS LoadedOrder
        , t.destination                                                            AS DestinationPlace
        , 'GB' COLLATE utf8mb4_unicode_ci                                          AS DestinationCountryCode
        , NULL                                                                     AS DestinationCountryOther
        , (fld.last_number + tf.number_offset + 2)                                 AS UnloadedOrder
      FROM trip_flags tf
      JOIN international_trip t ON t.id = tf.trip_id
      JOIN first_and_last_actions_details fld ON fld.trip_id = tf.trip_id
      WHERE tf.has_return_empty = 1
    ),
    
    -- Merge all fully-shaped rows, then number them
    prepared AS (
      SELECT * FROM outbound_rows
      UNION ALL
      SELECT * FROM paired_loadings
      UNION ALL
      SELECT * FROM lone_loadings
      UNION ALL
      SELECT * FROM return_rows
    ),
    numbered AS (
      SELECT
        p.*,
        ROW_NUMBER() OVER (PARTITION BY p.TripID ORDER BY p.step_order, p.ActionType) AS RowNumber
      FROM prepared p
    )
    
    SELECT
      ConsignmentID
      , TripID
      , ActionType
      , MOA
      , TypeOfGoods
      , TypeOfGoodsOther
      , DangerousGoodsCode
      , WeightOfGoodsCarried
      , OriginPlace
      , OriginCountryCode
      , OriginCountryOther
      , LoadedOrder
      , DestinationPlace
      , DestinationCountryCode
      , DestinationCountryOther
      , UnloadedOrder
      , RowNumber
    FROM numbered
    ORDER BY TripID, RowNumber
);
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP FUNCTION IF EXISTS irhs_empty_uuid');
        $this->addSql('DROP VIEW IF EXISTS view_international_consignments');
    }
}
