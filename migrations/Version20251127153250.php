<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251127153250 extends AbstractMigration
{

    public function getDescription(): string
    {
        return 'Pre-enquiry export view';
    }

    public function up(Schema $schema): void
    {
        $this->addSql($this->getPreEnquiryQuery());
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS view_export_pre_enquiry');
    }

    protected function getPreEnquiryQuery(): string
    {
        return <<<SQL
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
        , per.total_vehicle_count                     AS NumberOfHgvs
        , per.international_journey_vehicle_count     AS NumberOfHgvsForIntJourneys
        , per.annual_journey_estimate                 AS EstimatedNumberOfIntJourneys
        , per.number_of_employees                     AS NumberOfEmployees
    FROM pre_enquiry AS pe
    LEFT JOIN pre_enquiry_response AS per ON pe.id = per.pre_enquiry_id
    WHERE pe.state IN ('closed')
);
SQL;
    }
}
