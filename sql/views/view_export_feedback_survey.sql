CREATE OR REPLACE VIEW view_export_feedback_survey AS (
    SELECT f.id                          AS FeedbackID,
           COALESCE(sd.id, si.id, sp.id) AS SurveyID,
           CASE
               WHEN sd.id IS NOT NULL THEN 'domestic'
               WHEN si.id IS NOT NULL THEN 'international'
               WHEN sp.id IS NOT NULL THEN 'pre-enquiry'
           END                           AS SurveyType,
           CASE
               WHEN sd.id IS NOT NULL THEN sd.registration_mark
               WHEN si.id IS NOT NULL THEN si.reference_number
               WHEN sp.id IS NOT NULL THEN sp.reference_number
           END                           AS RefOrReg,
           f.created_at                  AS CreatedAt,
           f.experience_rating           AS ExperienceRating,
           f.has_completed_paper_survey  AS HasCompletedPaperSurvey,
           f.comparison_rating           AS ComparisonRating,
           f.time_to_complete            AS TimeToComplete,
           f.had_issues                  AS HadIssues,
           f.issue_details               AS IssueDetails,
           f.comments                    AS Comments
    FROM feedback AS f
         LEFT JOIN domestic_survey sd ON sd.feedback_id = f.id
         LEFT JOIN international_survey si ON si.feedback_id = f.id
         LEFT JOIN pre_enquiry sp ON sp.feedback_id = f.id
    ORDER BY f.created_at
);
