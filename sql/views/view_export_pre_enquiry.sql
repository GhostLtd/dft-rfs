CREATE OR REPLACE VIEW view_export_pre_enquiry AS (
    SELECT
        pe.id                                         AS PreEnquiryID
        , YEAR(pe.submission_date)                    AS SubmissionYear
        , MONTH(pe.submission_date)                   AS SubmissionMonth
        , pe.reference_number                         AS ReferenceNumber
        , pe.dispatch_date                            AS DispatchDate
        , NOT per.is_correct_company_name             AS WasCompanyNameCorrected
        , per.company_name                            AS CompanyName
        , per.contact_name                            AS ContactName
        , per.contact_telephone                       AS ContactPhone
        , per.contact_email                           AS ContactEmail
        , NOT per.is_correct_address                  AS WasAddressCorrected
        # address line1 is always the same as company name.
        , per.contact_address_line2                   AS AddressLine1
        , per.contact_address_line3                   AS AddressLine2
        , per.contact_address_line4                   AS AddressLine3
        , per.contact_address_line5                   AS AddressLine4
        , per.contact_address_line6                   AS AddressLine5
        , per.contact_address_postcode                AS AddressPostcode
        , per.total_hgv_count                         AS NumberOfHgvs
        , per.international_journey_hgv_count         AS NumberOfHgvsForIntJourneys
        , per.annual_hgv_journey_estimate             AS EstimatedNumberOfIntHgvJourneys
        , per.total_lgv_count                         AS NumberOfLgvs
        , per.international_journey_lgv_count         AS NumberOfLgvsForIntJourneys
        , per.annual_lgv_journey_estimate             AS EstimatedNumberOfIntLgvJourneys
        , per.number_of_employees                     AS NumberOfEmployees
    FROM pre_enquiry AS pe
    LEFT JOIN pre_enquiry_response AS per ON pe.id = per.pre_enquiry_id
    WHERE pe.state IN ('closed')
);
