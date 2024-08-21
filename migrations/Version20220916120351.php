<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220916120351 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Transition to use Symfony-generated UUIDs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE domestic_day CHANGE id id CHAR(36) NOT NULL, CHANGE response_id response_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE domestic_day_stop CHANGE id id CHAR(36) NOT NULL, CHANGE day_id day_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE domestic_day_summary CHANGE id id CHAR(36) NOT NULL, CHANGE day_id day_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE domestic_driver_availablity CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE domestic_survey CHANGE id id CHAR(36) NOT NULL, CHANGE original_survey_id original_survey_id CHAR(36) DEFAULT NULL, CHANGE driver_availability_id driver_availability_id CHAR(36) DEFAULT NULL, CHANGE feedback_id feedback_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE domestic_survey_notify_api_responses CHANGE survey_id survey_id CHAR(36) NOT NULL, CHANGE notify_api_response_id notify_api_response_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE domestic_survey_note CHANGE id id CHAR(36) NOT NULL, CHANGE survey_id survey_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE domestic_survey_response CHANGE id id CHAR(36) NOT NULL, CHANGE survey_id survey_id CHAR(36) NOT NULL, CHANGE vehicle_id vehicle_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE domestic_vehicle CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE feedback CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE international_action CHANGE id id CHAR(36) NOT NULL, CHANGE loading_action_id loading_action_id CHAR(36) DEFAULT NULL, CHANGE trip_id trip_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE international_company CHANGE id id CHAR(36) NOT NULL, CHANGE sampling_group_id sampling_group_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE international_notification_interception CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE international_notification_interception_company_name CHANGE id id CHAR(36) NOT NULL, CHANGE notification_interception_id notification_interception_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE international_survey CHANGE id id CHAR(36) NOT NULL, CHANGE company_id company_id CHAR(36) NOT NULL, CHANGE feedback_id feedback_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey_notify_api_responses CHANGE survey_id survey_id CHAR(36) NOT NULL, CHANGE notify_api_response_id notify_api_response_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE international_survey_note CHANGE id id CHAR(36) NOT NULL, CHANGE survey_id survey_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE international_survey_response CHANGE id id CHAR(36) NOT NULL, CHANGE survey_id survey_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE international_trip CHANGE id id CHAR(36) NOT NULL, CHANGE vehicle_id vehicle_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE international_vehicle CHANGE id id CHAR(36) NOT NULL, CHANGE survey_response_id survey_response_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE maintenance_lock CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE maintenance_warning CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE notify_api_response CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE passcode_user CHANGE id id CHAR(36) NOT NULL, CHANGE domestic_survey_id domestic_survey_id CHAR(36) DEFAULT NULL, CHANGE international_survey_id international_survey_id CHAR(36) DEFAULT NULL, CHANGE pre_enquiry_id pre_enquiry_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry CHANGE id id CHAR(36) NOT NULL, CHANGE feedback_id feedback_id CHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry_notify_api_responses CHANGE survey_id survey_id CHAR(36) NOT NULL, CHANGE notify_api_response_id notify_api_response_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE pre_enquiry_note CHANGE id id CHAR(36) NOT NULL, CHANGE pre_enquiry_id pre_enquiry_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE pre_enquiry_response CHANGE id id CHAR(36) NOT NULL, CHANGE pre_enquiry_id pre_enquiry_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE sampling_group CHANGE id id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE sampling_schema CHANGE id id CHAR(36) NOT NULL, CHANGE day_id day_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE sampling_schema_day CHANGE id id CHAR(36) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_log CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_day CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE response_id response_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_day_stop CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE day_id day_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_day_summary CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE day_id day_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_driver_availablity CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_survey CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE original_survey_id original_survey_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE driver_availability_id driver_availability_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE feedback_id feedback_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_survey_note CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE survey_id survey_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_survey_notify_api_responses CHANGE survey_id survey_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE notify_api_response_id notify_api_response_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_survey_response CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE survey_id survey_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE vehicle_id vehicle_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_vehicle CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE feedback CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_action CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE loading_action_id loading_action_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE trip_id trip_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_company CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE sampling_group_id sampling_group_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_notification_interception CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_notification_interception_company_name CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE notification_interception_id notification_interception_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_survey CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE company_id company_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE feedback_id feedback_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_survey_note CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE survey_id survey_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_survey_notify_api_responses CHANGE survey_id survey_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE notify_api_response_id notify_api_response_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_survey_response CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE survey_id survey_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_trip CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE vehicle_id vehicle_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_vehicle CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE survey_response_id survey_response_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE maintenance_lock CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE maintenance_warning CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE notify_api_response CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE passcode_user CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE domestic_survey_id domestic_survey_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE international_survey_id international_survey_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE pre_enquiry_id pre_enquiry_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE pre_enquiry CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE feedback_id feedback_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE pre_enquiry_note CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE pre_enquiry_id pre_enquiry_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE pre_enquiry_notify_api_responses CHANGE survey_id survey_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE notify_api_response_id notify_api_response_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE pre_enquiry_response CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE pre_enquiry_id pre_enquiry_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE sampling_group CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE sampling_schema CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', CHANGE day_id day_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE sampling_schema_day CHANGE id id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\'');
    }
}
