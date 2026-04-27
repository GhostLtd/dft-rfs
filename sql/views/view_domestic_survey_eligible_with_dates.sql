CREATE OR REPLACE VIEW view_domestic_survey_eligible_with_dates AS (
    WITH base_rows AS (
        SELECT 
            s.id
            , s.survey_period_start                                                                    AS period_start
            , DATE_SUB(s.survey_period_start, INTERVAL (DAYOFWEEK(s.survey_period_start) + 5) % 7 DAY) AS monday_of_date
            , YEAR(s.survey_period_start)                                                              AS calendar_year
        FROM domestic_survey s
        WHERE
            s.state IN ('new', 'invitation pending', 'invitation failed', 'invited', 'in-progress', 'closed', 'approved')
    )
    , year_boundaries AS (
        SELECT 
            b.id
            , b.period_start
            , b.monday_of_date
            , b.calendar_year
            , DATE(CONCAT(b.calendar_year, '-01-01'))     AS jan1_current
            , DATE(CONCAT(b.calendar_year + 1, '-01-01')) AS jan1_next
        FROM base_rows b
    )
    , aligned_year_starts AS (
        SELECT 
            yb.id
            , yb.period_start
            , yb.monday_of_date
            , yb.calendar_year
            , DATE_SUB(yb.jan1_current, INTERVAL (DAYOFWEEK(yb.jan1_current) + 5) % 7 DAY) AS start_of_current_weekyear
            , DATE_SUB(yb.jan1_next, INTERVAL (DAYOFWEEK(yb.jan1_next) + 5) % 7 DAY)       AS start_of_next_weekyear
        FROM year_boundaries yb
    )
    , weekyear_resolved AS (
        SELECT 
            ays.id
            , ays.period_start
            , ays.monday_of_date
            , CASE
                WHEN ays.monday_of_date >= ays.start_of_next_weekyear THEN ays.calendar_year + 1
                WHEN ays.monday_of_date < ays.start_of_current_weekyear THEN ays.calendar_year - 1
                ELSE ays.calendar_year
            END AS week_year
        FROM aligned_year_starts ays
    )
    , week_anchor AS (
        SELECT 
            wr.id
            , wr.period_start
            , wr.monday_of_date
            , wr.week_year
            , DATE_SUB(
                DATE(CONCAT(wr.week_year, '-01-01')),
                INTERVAL (DAYOFWEEK(DATE(CONCAT(wr.week_year, '-01-01'))) + 5) % 7 DAY
            ) AS start_of_week_year
        FROM weekyear_resolved wr
    )
    , week_numbers AS (
        SELECT 
            wa.id
            , wa.period_start
            , wa.week_year
            , 1 + FLOOR(DATEDIFF(wa.monday_of_date, wa.start_of_week_year) / 7) AS week_number
        FROM week_anchor wa
    )
    SELECT
        wn.week_year
        , wn.week_number
        , CASE
            WHEN wn.week_number BETWEEN 1 AND 13 THEN 1
            WHEN wn.week_number BETWEEN 14 AND 26 THEN 2
            WHEN wn.week_number BETWEEN 27 AND 39 THEN 3
            ELSE 4
        END AS quarter,
        s.*
    FROM base_rows AS b
    JOIN week_numbers AS wn ON wn.id = b.id
    JOIN domestic_survey AS s ON s.id = b.id
);
