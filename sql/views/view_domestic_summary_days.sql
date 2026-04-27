-- @depends view_domestic_days

CREATE OR REPLACE VIEW view_domestic_summary_days AS (
    SELECT
        d.WeekBasedYear
        , d.WeekBasedWeek
        , d.WeekBasedQuarter
        , d.DayNumber
        , d.SurveyID
        , ds.id                                                                                       AS JourneyID
        , CAST(NULL AS SIGNED)                                                                        AS RowNumber
        , ds.origin_location                                                                          AS Origin
        , ds.destination_location                                                                     AS Destination
        , ds.goods_description                                                                        AS TypeOfGoods
        , ds.goods_description_other                                                                  AS TypeOfGoodsOther
        , CASE
            WHEN ds.distance_travelled_loaded_unit = 'kilometres' THEN ds.distance_travelled_loaded_value
            WHEN ds.distance_travelled_loaded_unit = 'miles' THEN ds.distance_travelled_loaded_value * 1.609344
        END                                                                                           AS DistanceTravelledLoaded
        , CASE
            WHEN ds.distance_travelled_unloaded_unit = 'kilometres' THEN ds.distance_travelled_unloaded_value
            WHEN ds.distance_travelled_unloaded_unit = 'miles' THEN ds.distance_travelled_unloaded_value * 1.609344
        END                                                                                           AS DistanceTravelledEmpty
        , CAST(NULL AS SIGNED)                                                                        AS WeightOfGoodsCarried
        , IF(ds.weight_of_goods_unloaded IS NULL, 0, ds.weight_of_goods_unloaded)                     AS WeightOfGoodsDelivered
        , IF(ds.weight_of_goods_loaded IS NULL, 0, ds.weight_of_goods_loaded)                         AS WeightOfGoodsCollected
        , ds.number_of_stops_unloading                                                                AS NumberOfStopsForDelivery
        , ds.number_of_stops_loading                                                                  AS NumberOfStopsForCollection
        , ds.number_of_stops_loading_and_unloading                                                    AS NumberOfStopsForBoth
        , ds.furthest_stop                                                                            AS FurthestStop
        , IF(ds.cargo_type_code IS NULL AND ds.goods_description = 'empty', 'NS', ds.cargo_type_code) AS MOA
        , ds.border_crossing_location                                                                 AS WhereBorderCrossed
        , d.DayOfWeek
        , CAST(NULL AS SIGNED)                                                                        AS WasLimitedBySpace
        , CAST(NULL AS SIGNED)                                                                        AS WasLimitedByWeight
        , IF(ds.hazardous_goods_code > 0, ds.hazardous_goods_code, NULL)                              AS DangerousGoodsCode
        , CASE goods_transferred_from
            WHEN 0 THEN NULL
            WHEN 1 THEN 'port'
            WHEN 2 THEN 'rail'
            WHEN 4 THEN 'air'
        END                                                                                           AS GoodsTransferredFrom
        , CASE goods_transferred_to
            WHEN 0 THEN NULL
            WHEN 1 THEN 'port'
            WHEN 2 THEN 'rail'
            WHEN 4 THEN 'air'
        END                                                                                           AS GoodsTransferredTo
        , CAST('summary' AS CHAR(7) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci                 AS JourneyType
    FROM view_domestic_days d
    STRAIGHT_JOIN domestic_day_summary ds ON d.DayID = ds.day_id
);
