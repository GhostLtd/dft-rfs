-- @depends view_international_firms
-- @depends view_international_trips
-- @depends view_international_consignments

CREATE OR REPLACE VIEW view_export_international_survey AS (
SELECT
    f.SurveyID
    , f.ReferenceNumber
    , f.SurveyStatus
    , f.SubmissionDate
    , f.SurveyPeriodStart
    , f.SurveyPeriodEnd
    , f.NumberOfReminders
    , f.NumberOfInternationalHGVJourneys
    , f.NumberOfInternationalLGVJourneys
    , f.ActivityStatus
    , f.ActivityStatusOther
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
