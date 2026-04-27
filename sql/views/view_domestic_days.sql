-- @depends view_domestic_survey_eligible_with_dates

CREATE OR REPLACE VIEW view_domestic_days AS (
    SELECT
      s.id                      AS SurveyID
      , s.survey_period_start   AS SurveyPeriodStart
      , s.week_year             AS WeekBasedYear
      , s.week_number           AS WeekBasedWeek
      , s.quarter               AS WeekBasedQuarter
      , d.id                    AS DayID
      , d.number                AS DayNumber
      , CAST(DATE_FORMAT(
               DATE_ADD(s.survey_period_start, INTERVAL (d.number - 1) DAY),
               '%a'
        ) AS CHAR(3) CHARACTER SET utf8mb4) COLLATE utf8mb4_unicode_ci
                                AS DayOfWeek
    FROM view_domestic_survey_eligible_with_dates s
    JOIN domestic_survey_response r ON r.survey_id = s.id
    JOIN domestic_day d ON d.response_id = r.id
);
