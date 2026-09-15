-- @depends uuid6_to_timestamp
CREATE OR REPLACE VIEW view_international_firms AS (
    SELECT
        s.id                                        AS SurveyID
        , s.reference_number                        AS ReferenceNumber
        , s.survey_period_start                     AS SurveyPeriodStart
        , s.survey_period_end                       AS SurveyPeriodEnd
        , s.state                                   AS SurveyStatus
        , s.submission_date                         AS SubmissionDate
        , CASE
            WHEN EXISTS (SELECT 1 FROM international_survey_notification n WHERE n.survey_id = s.id AND n.type = 'reminder-2') THEN 2
            WHEN EXISTS (SELECT 1 FROM international_survey_notification n WHERE n.survey_id = s.id AND n.type = 'reminder-1') THEN 1
            ELSE 0
          END                                       AS NumberOfReminders
        , sr.annual_international_hgv_journey_count AS NumberOfInternationalHGVJourneys
        , sr.annual_international_lgv_journey_count AS NumberOfInternationalLGVJourneys
        , sr.activity_status                        AS ActivityStatus
        , CASE
            WHEN sr.activity_status = 'no-road-haulage' THEN sr.activity_status_other
          END                                       AS ActivityStatusOther
        , CASE
            WHEN
              -- has LGV data => definitely the new version (column only populated post-deploy)
              sr.annual_international_lgv_journey_count IS NOT NULL
              -- response exists, empty/inactive, first engaged on/after deploy
              OR (
                sr.id IS NOT NULL
                AND (sr.activity_status IS NULL OR sr.activity_status != 'still-active')
                AND uuid6_to_timestamp(sr.id) >= '2026-03-11'
              )
              -- no response created yet, but the survey was issued on/after deploy
              OR (
                sr.id IS NULL
                AND (SELECT MAX(n.sent_at) FROM international_survey_notification n WHERE n.survey_id = s.id AND n.type = 'invite') >= '2026-03-11'
              )
            THEN 1 ELSE 0
          END                                       AS AskedNewLgvQuestions
        , s.reason_for_empty_survey                 AS ReasonForEmptySurvey
        , CASE
            WHEN s.reason_for_empty_survey = 'other' THEN s.reason_for_empty_survey_other
          END                                       AS ReasonForEmptySurveyOther
        , sr.business_nature                        AS BusinessNature
        , sr.number_of_employees                    AS NumberOfEmployees
    FROM international_survey AS s
    LEFT JOIN international_survey_response AS sr ON s.id = sr.survey_id
);
