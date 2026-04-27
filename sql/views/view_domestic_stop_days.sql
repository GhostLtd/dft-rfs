-- @depends view_domestic_days

CREATE OR REPLACE VIEW view_domestic_stop_days AS (
    SELECT
        d.WeekBasedYear
        , d.WeekBasedWeek
        , d.WeekBasedQuarter          
        , d.DayNumber
        , d.SurveyID
        , ds.id                                                                                       AS JourneyID
        , ds.number                                                                                   AS RowNumber
        , ds.origin_location                                                                          AS Origin
        , ds.destination_location                                                                     AS Destination
        , ds.goods_description                                                                        AS TypeOfGoods
        , ds.goods_description_other                                                                  AS TypeOfGoodsOther
        , CASE
            WHEN ds.goods_description = 'empty' THEN 0
            WHEN ds.distance_travelled_unit = 'kilometres' THEN ds.distance_travelled_value
            WHEN ds.distance_travelled_unit = 'miles' THEN ds.distance_travelled_value * 1.609344
        END                                                                                           AS DistanceTravelledLoaded
        , CASE
            WHEN ds.goods_description != 'empty' THEN 0
            WHEN ds.distance_travelled_unit = 'kilometres' THEN ds.distance_travelled_value
            WHEN ds.distance_travelled_unit = 'miles' THEN ds.distance_travelled_value * 1.609344
        END                                                                                           AS DistanceTravelledEmpty
        , IF(ds.weight_of_goods_carried IS NULL, 0, ds.weight_of_goods_carried)                       AS WeightOfGoodsCarried

        # summary-only rows
        , CAST(NULL AS SIGNED)                                                                        AS WeightOfGoodsDelivered
        , CAST(NULL AS SIGNED)                                                                        AS WeightOfGoodsCollected
        , CAST(NULL AS SIGNED)                                                                        AS NumberOfStopsForDelivery
        , CAST(NULL AS SIGNED)                                                                        AS NumberOfStopsForCollection
        , CAST(NULL AS SIGNED)                                                                        AS NumberOfStopsForBoth
        , CAST(NULL AS CHAR(255)  CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci                   AS FurthestStop

        , IF(ds.cargo_type_code IS NULL AND ds.goods_description = 'empty', 'NS', ds.cargo_type_code) AS MOA
        , ds.border_crossing_location                                                                 AS WhereBorderCrossed
        , d.DayOfWeek
        , COALESCE(ds.was_limited_by_space, 0)                                                        AS WasLimitedBySpace
        , COALESCE(ds.was_limited_by_weight, 0)                                                       AS WasLimitedByWeight
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
        , CAST('stop' AS CHAR(7) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci                    AS JourneyType
    FROM view_domestic_days d
    STRAIGHT_JOIN domestic_day_stop ds ON d.DayID = ds.day_id
);
