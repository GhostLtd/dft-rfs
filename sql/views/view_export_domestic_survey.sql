-- @depends view_domestic_survey_vehicles
-- @depends view_domestic_stop_days
-- @depends view_domestic_summary_days

CREATE OR REPLACE VIEW view_export_domestic_survey AS (
    WITH journeys AS (
        SELECT * FROM view_domestic_stop_days
        UNION ALL
        SELECT * FROM view_domestic_summary_days
    )
    SELECT
        v.*
        , CASE WHEN j.JourneyID IS NULL THEN 1 ELSE 0 END AS NotUsed
        , j.JourneyID
        , j.DayNumber
        , j.JourneyType
        , j.RowNumber
        , j.Origin
        , j.Destination
        , j.TypeOfGoods
        , j.TypeOfGoodsOther
        , j.DistanceTravelledLoaded
        , j.DistanceTravelledEmpty
        , j.WeightOfGoodsCarried
        , j.WeightOfGoodsDelivered
        , j.WeightOfGoodsCollected
        , j.NumberOfStopsForDelivery
        , j.NumberOfStopsForCollection
        , j.NumberOfStopsForBoth
        , j.FurthestStop
        , j.MOA
        , j.WhereBorderCrossed
        , j.DayOfWeek
        , j.WasLimitedBySpace
        , j.WasLimitedByWeight
        , j.DangerousGoodsCode
        , j.GoodsTransferredFrom
        , j.GoodsTransferredTo
    FROM view_domestic_survey_vehicles v
    LEFT JOIN journeys j ON j.SurveyID = v.SurveyID
);
