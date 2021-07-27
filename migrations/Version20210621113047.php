<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210621113047 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("CREATE TABLE `sessions` (`sess_id` VARBINARY(128) NOT NULL PRIMARY KEY, `sess_data` MEDIUMBLOB NOT NULL, `sess_lifetime` INTEGER UNSIGNED NOT NULL, `sess_time` INTEGER UNSIGNED NOT NULL ) COLLATE utf8mb4_bin, ENGINE = InnoDB;");
        $this->addSql("CREATE TABLE messenger_messages (id bigint auto_increment primary key, body longtext not null, headers      longtext     not null,     queue_name   varchar(190) not null,    created_at   datetime     not null,    available_at datetime     not null,    delivered_at datetime     null, INDEX IDX_75EA56E016BA31DB (delivered_at), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E0FB7336F0 (queue_name))  DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_log (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', category VARCHAR(16) NOT NULL, username VARCHAR(255) NOT NULL, entity_id VARCHAR(255) NOT NULL, entity_class VARCHAR(255) NOT NULL, timestamp DATETIME NOT NULL, data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domestic_day (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', response_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', number SMALLINT NOT NULL, has_more_than_five_stops TINYINT(1) DEFAULT NULL, INDEX IDX_E35E66FFFBF32840 (response_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domestic_day_stop (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', day_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', number SMALLINT NOT NULL, weight_of_goods_carried INT DEFAULT NULL, was_at_capacity TINYINT(1) DEFAULT NULL, was_limited_by_weight TINYINT(1) DEFAULT NULL, was_limited_by_space TINYINT(1) DEFAULT NULL, origin_location VARCHAR(255) NOT NULL, destination_location VARCHAR(255) NOT NULL, goods_loaded TINYINT(1) NOT NULL, goods_transferred_from SMALLINT DEFAULT NULL, goods_unloaded TINYINT(1) NOT NULL, goods_transferred_to SMALLINT DEFAULT NULL, border_crossing_location VARCHAR(255) DEFAULT NULL, goods_description VARCHAR(255) NOT NULL, goods_description_other VARCHAR(255) DEFAULT NULL, hazardous_goods_code VARCHAR(5) DEFAULT NULL, cargo_type_code VARCHAR(4) DEFAULT NULL, distance_travelled_value NUMERIC(10, 1) DEFAULT NULL, distance_travelled_unit VARCHAR(12) DEFAULT NULL, INDEX IDX_232BAA6B9C24126 (day_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domestic_day_summary (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', day_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', furthest_stop VARCHAR(255) NOT NULL, weight_of_goods_loaded INT NOT NULL, weight_of_goods_unloaded INT NOT NULL, number_of_stops_loading INT NOT NULL, number_of_stops_unloading INT NOT NULL, number_of_stops_loading_and_unloading INT NOT NULL, origin_location VARCHAR(255) NOT NULL, destination_location VARCHAR(255) NOT NULL, goods_loaded TINYINT(1) NOT NULL, goods_transferred_from SMALLINT DEFAULT NULL, goods_unloaded TINYINT(1) NOT NULL, goods_transferred_to SMALLINT DEFAULT NULL, border_crossing_location VARCHAR(255) DEFAULT NULL, goods_description VARCHAR(255) NOT NULL, goods_description_other VARCHAR(255) DEFAULT NULL, hazardous_goods_code VARCHAR(5) DEFAULT NULL, cargo_type_code VARCHAR(4) DEFAULT NULL, distance_travelled_loaded_value NUMERIC(10, 1) DEFAULT NULL, distance_travelled_loaded_unit VARCHAR(12) DEFAULT NULL, distance_travelled_unloaded_value NUMERIC(10, 1) DEFAULT NULL, distance_travelled_unloaded_unit VARCHAR(12) DEFAULT NULL, UNIQUE INDEX UNIQ_2DBD2CAF9C24126 (day_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domestic_survey (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', is_northern_ireland TINYINT(1) NOT NULL, registration_mark VARCHAR(10) NOT NULL, survey_period_start DATE DEFAULT NULL, survey_period_end DATE DEFAULT NULL, notified_date DATETIME DEFAULT NULL, first_reminder_sent_date DATETIME DEFAULT NULL, second_reminder_sent_date DATETIME DEFAULT NULL, notify_api_responses LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', response_start_date DATETIME DEFAULT NULL, submission_date DATETIME DEFAULT NULL, state VARCHAR(20) NOT NULL, invitation_email VARCHAR(255) DEFAULT NULL, invitation_address_line5 VARCHAR(255) DEFAULT NULL, invitation_address_line6 VARCHAR(255) DEFAULT NULL, invitation_address_line1 VARCHAR(255) DEFAULT NULL, invitation_address_line2 VARCHAR(255) DEFAULT NULL, invitation_address_line3 VARCHAR(255) DEFAULT NULL, invitation_address_line4 VARCHAR(255) DEFAULT NULL, invitation_address_postcode VARCHAR(10) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domestic_survey_note (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', survey_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', note LONGTEXT NOT NULL, created_by VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_1204C091B3FE509D (survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domestic_survey_response (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', survey_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', vehicle_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', hiree_name VARCHAR(255) DEFAULT NULL, hiree_email VARCHAR(255) DEFAULT NULL, hiree_telephone VARCHAR(50) DEFAULT NULL, new_owner_name VARCHAR(255) DEFAULT NULL, new_owner_email VARCHAR(255) DEFAULT NULL, unable_to_complete_date DATE DEFAULT NULL, is_in_possession_of_vehicle VARCHAR(24) DEFAULT NULL, reason_for_empty_survey VARCHAR(24) DEFAULT NULL, actual_vehicle_location VARCHAR(255) DEFAULT NULL, business_nature VARCHAR(255) DEFAULT NULL, contact_name VARCHAR(255) DEFAULT NULL, contact_telephone VARCHAR(50) DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, number_of_employees VARCHAR(20) DEFAULT NULL, hiree_address_line1 VARCHAR(255) DEFAULT NULL, hiree_address_line2 VARCHAR(255) DEFAULT NULL, hiree_address_line3 VARCHAR(255) DEFAULT NULL, hiree_address_line4 VARCHAR(255) DEFAULT NULL, hiree_address_postcode VARCHAR(10) DEFAULT NULL, new_owner_address_line1 VARCHAR(255) DEFAULT NULL, new_owner_address_line2 VARCHAR(255) DEFAULT NULL, new_owner_address_line3 VARCHAR(255) DEFAULT NULL, new_owner_address_line4 VARCHAR(255) DEFAULT NULL, new_owner_address_postcode VARCHAR(10) DEFAULT NULL, UNIQUE INDEX UNIQ_65962CB3FE509D (survey_id), UNIQUE INDEX UNIQ_65962C545317D1 (vehicle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE domestic_vehicle (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', registration_mark VARCHAR(10) NOT NULL, operation_type VARCHAR(255) DEFAULT NULL, gross_weight INT DEFAULT NULL, carrying_capacity INT DEFAULT NULL, trailer_configuration INT DEFAULT NULL, axle_configuration INT DEFAULT NULL, body_type VARCHAR(24) DEFAULT NULL, fuel_quantity_value NUMERIC(8, 2) DEFAULT NULL, fuel_quantity_unit VARCHAR(8) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_action (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', loading_action_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', trip_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', number SMALLINT NOT NULL, name VARCHAR(255) NOT NULL, country VARCHAR(2) DEFAULT NULL, country_other VARCHAR(255) DEFAULT NULL, goods_description VARCHAR(255) DEFAULT NULL, goods_description_other VARCHAR(255) DEFAULT NULL, weight_unloaded_all TINYINT(1) DEFAULT NULL, weight_of_goods INT DEFAULT NULL, loading TINYINT(1) NOT NULL, hazardous_goods_code VARCHAR(5) DEFAULT NULL, cargo_type_code VARCHAR(4) DEFAULT NULL, INDEX IDX_A835D57BF4C36F23 (loading_action_id), INDEX IDX_A835D57BA5BC2E0E (trip_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_company (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', sampling_group_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', business_name VARCHAR(255) NOT NULL, INDEX IDX_9686AACA1B01E374 (sampling_group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_consignment (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', loading_stop_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', unloading_stop_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', trip_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', goods_description VARCHAR(255) NOT NULL, goods_description_other VARCHAR(255) DEFAULT NULL, weight_of_goods_carried INT NOT NULL, hazardous_goods_code VARCHAR(5) DEFAULT NULL, cargo_type_code VARCHAR(4) DEFAULT NULL, INDEX IDX_C28ABA766BDC8A9 (loading_stop_id), INDEX IDX_C28ABA7691586320 (unloading_stop_id), INDEX IDX_C28ABA76A5BC2E0E (trip_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_crossing_route (id INT AUTO_INCREMENT NOT NULL, uk_port VARCHAR(255) NOT NULL, foreign_port VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_stop (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', trip_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', name VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, number SMALLINT NOT NULL, INDEX IDX_290BDE08A5BC2E0E (trip_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_survey (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', company_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', reference_number VARCHAR(255) NOT NULL, survey_period_start DATE DEFAULT NULL, survey_period_end DATE DEFAULT NULL, notified_date DATETIME DEFAULT NULL, first_reminder_sent_date DATETIME DEFAULT NULL, second_reminder_sent_date DATETIME DEFAULT NULL, notify_api_responses LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', response_start_date DATETIME DEFAULT NULL, submission_date DATETIME DEFAULT NULL, state VARCHAR(20) NOT NULL, invitation_email VARCHAR(255) DEFAULT NULL, invitation_address_line5 VARCHAR(255) DEFAULT NULL, invitation_address_line6 VARCHAR(255) DEFAULT NULL, invitation_address_line1 VARCHAR(255) DEFAULT NULL, invitation_address_line2 VARCHAR(255) DEFAULT NULL, invitation_address_line3 VARCHAR(255) DEFAULT NULL, invitation_address_line4 VARCHAR(255) DEFAULT NULL, invitation_address_postcode VARCHAR(10) DEFAULT NULL, INDEX IDX_42A6C215979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_survey_note (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', survey_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', note LONGTEXT NOT NULL, created_by VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_F966B825B3FE509D (survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_survey_response (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', survey_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', annual_international_journey_count INT NOT NULL, activity_status VARCHAR(24) DEFAULT NULL, reason_for_empty_survey VARCHAR(24) DEFAULT NULL, reason_for_empty_survey_other VARCHAR(255) DEFAULT NULL, initial_details_signed_off TINYINT(1) DEFAULT NULL, business_nature VARCHAR(255) DEFAULT NULL, contact_name VARCHAR(255) DEFAULT NULL, contact_telephone VARCHAR(50) DEFAULT NULL, contact_email VARCHAR(255) DEFAULT NULL, number_of_employees VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_A88444DCB3FE509D (survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_trip (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', vehicle_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', origin VARCHAR(255) NOT NULL, destination VARCHAR(255) NOT NULL, outbound_date DATE NOT NULL, outbound_uk_port VARCHAR(255) NOT NULL, outbound_foreign_port VARCHAR(255) NOT NULL, outbound_was_limited_by_space TINYINT(1) NOT NULL, outbound_was_limited_by_weight TINYINT(1) NOT NULL, outbound_was_empty TINYINT(1) NOT NULL, return_date DATE NOT NULL, return_foreign_port VARCHAR(255) NOT NULL, return_uk_port VARCHAR(255) NOT NULL, return_was_limited_by_space TINYINT(1) NOT NULL, return_was_limited_by_weight TINYINT(1) NOT NULL, return_was_empty TINYINT(1) NOT NULL, countries_transitted LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', countries_transitted_other LONGTEXT DEFAULT NULL, is_swapped_trailer TINYINT(1) NOT NULL, axle_configuration INT DEFAULT NULL, is_changed_body_type TINYINT(1) DEFAULT NULL, body_type VARCHAR(24) DEFAULT NULL, gross_weight INT DEFAULT NULL, carrying_capacity INT DEFAULT NULL, trailer_configuration INT DEFAULT NULL, round_trip_distance_value NUMERIC(10, 1) DEFAULT NULL, round_trip_distance_unit VARCHAR(12) DEFAULT NULL, INDEX IDX_E60B3D85545317D1 (vehicle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_vehicle (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', survey_response_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', registration_mark VARCHAR(10) NOT NULL, operation_type VARCHAR(255) DEFAULT NULL, gross_weight INT DEFAULT NULL, carrying_capacity INT DEFAULT NULL, trailer_configuration INT DEFAULT NULL, axle_configuration INT DEFAULT NULL, body_type VARCHAR(24) DEFAULT NULL, INDEX IDX_C2B94703430BF745 (survey_response_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_lock (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', whitelisted_ips LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE maintenance_warning (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', start DATETIME NOT NULL, end TIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE passcode_user (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', domestic_survey_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', international_survey_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', pre_enquiry_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', username VARCHAR(10) NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_21B8B82EF85E0677 (username), UNIQUE INDEX UNIQ_21B8B82EF2CB3FAC (domestic_survey_id), UNIQUE INDEX UNIQ_21B8B82EBAD517F8 (international_survey_id), UNIQUE INDEX UNIQ_21B8B82EE4D0D43E (pre_enquiry_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pre_enquiry (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', company_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', reference_number VARCHAR(255) NOT NULL, notified_date DATETIME DEFAULT NULL, first_reminder_sent_date DATETIME DEFAULT NULL, second_reminder_sent_date DATETIME DEFAULT NULL, notify_api_responses LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', response_start_date DATETIME DEFAULT NULL, submission_date DATETIME DEFAULT NULL, state VARCHAR(20) NOT NULL, invitation_email VARCHAR(255) DEFAULT NULL, invitation_address_line5 VARCHAR(255) DEFAULT NULL, invitation_address_line6 VARCHAR(255) DEFAULT NULL, invitation_address_line1 VARCHAR(255) DEFAULT NULL, invitation_address_line2 VARCHAR(255) DEFAULT NULL, invitation_address_line3 VARCHAR(255) DEFAULT NULL, invitation_address_line4 VARCHAR(255) DEFAULT NULL, invitation_address_postcode VARCHAR(10) DEFAULT NULL, INDEX IDX_FD1E25C1979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pre_enquiry_note (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', pre_enquiry_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', note LONGTEXT NOT NULL, created_by VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_66AB05E2E4D0D43E (pre_enquiry_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pre_enquiry_response (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', pre_enquiry_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', is_correct_company_name TINYINT(1) NOT NULL, is_correct_address TINYINT(1) NOT NULL, company_name VARCHAR(255) NOT NULL, contact_name VARCHAR(255) NOT NULL, contact_telephone VARCHAR(50) NOT NULL, contact_email VARCHAR(255) NOT NULL, total_vehicle_count INT NOT NULL, international_journey_vehicle_count INT NOT NULL, number_of_employees VARCHAR(20) DEFAULT NULL, annual_journey_estimate INT NOT NULL, contact_address_line5 VARCHAR(255) DEFAULT NULL, contact_address_line6 VARCHAR(255) DEFAULT NULL, contact_address_line1 VARCHAR(255) DEFAULT NULL, contact_address_line2 VARCHAR(255) DEFAULT NULL, contact_address_line3 VARCHAR(255) DEFAULT NULL, contact_address_line4 VARCHAR(255) DEFAULT NULL, contact_address_postcode VARCHAR(10) DEFAULT NULL, UNIQUE INDEX UNIQ_907CD8F6E4D0D43E (pre_enquiry_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sampling_group (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', number SMALLINT NOT NULL, size_group SMALLINT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sampling_schema (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', day_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', size_group SMALLINT NOT NULL, week_number SMALLINT NOT NULL, INDEX IDX_59D747C79C24126 (day_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sampling_schema_day (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE domestic_day ADD CONSTRAINT FK_E35E66FFFBF32840 FOREIGN KEY (response_id) REFERENCES domestic_survey_response (id)');
        $this->addSql('ALTER TABLE domestic_day_stop ADD CONSTRAINT FK_232BAA6B9C24126 FOREIGN KEY (day_id) REFERENCES domestic_day (id)');
        $this->addSql('ALTER TABLE domestic_day_summary ADD CONSTRAINT FK_2DBD2CAF9C24126 FOREIGN KEY (day_id) REFERENCES domestic_day (id)');
        $this->addSql('ALTER TABLE domestic_survey_note ADD CONSTRAINT FK_1204C091B3FE509D FOREIGN KEY (survey_id) REFERENCES domestic_survey (id)');
        $this->addSql('ALTER TABLE domestic_survey_response ADD CONSTRAINT FK_65962CB3FE509D FOREIGN KEY (survey_id) REFERENCES domestic_survey (id)');
        $this->addSql('ALTER TABLE domestic_survey_response ADD CONSTRAINT FK_65962C545317D1 FOREIGN KEY (vehicle_id) REFERENCES domestic_vehicle (id)');
        $this->addSql('ALTER TABLE international_action ADD CONSTRAINT FK_A835D57BF4C36F23 FOREIGN KEY (loading_action_id) REFERENCES international_action (id)');
        $this->addSql('ALTER TABLE international_action ADD CONSTRAINT FK_A835D57BA5BC2E0E FOREIGN KEY (trip_id) REFERENCES international_trip (id)');
        $this->addSql('ALTER TABLE international_company ADD CONSTRAINT FK_9686AACA1B01E374 FOREIGN KEY (sampling_group_id) REFERENCES sampling_group (id)');
        $this->addSql('ALTER TABLE international_consignment ADD CONSTRAINT FK_C28ABA766BDC8A9 FOREIGN KEY (loading_stop_id) REFERENCES international_stop (id)');
        $this->addSql('ALTER TABLE international_consignment ADD CONSTRAINT FK_C28ABA7691586320 FOREIGN KEY (unloading_stop_id) REFERENCES international_stop (id)');
        $this->addSql('ALTER TABLE international_consignment ADD CONSTRAINT FK_C28ABA76A5BC2E0E FOREIGN KEY (trip_id) REFERENCES international_trip (id)');
        $this->addSql('ALTER TABLE international_stop ADD CONSTRAINT FK_290BDE08A5BC2E0E FOREIGN KEY (trip_id) REFERENCES international_trip (id)');
        $this->addSql('ALTER TABLE international_survey ADD CONSTRAINT FK_42A6C215979B1AD6 FOREIGN KEY (company_id) REFERENCES international_company (id)');
        $this->addSql('ALTER TABLE international_survey_note ADD CONSTRAINT FK_F966B825B3FE509D FOREIGN KEY (survey_id) REFERENCES international_survey (id)');
        $this->addSql('ALTER TABLE international_survey_response ADD CONSTRAINT FK_A88444DCB3FE509D FOREIGN KEY (survey_id) REFERENCES international_survey (id)');
        $this->addSql('ALTER TABLE international_trip ADD CONSTRAINT FK_E60B3D85545317D1 FOREIGN KEY (vehicle_id) REFERENCES international_vehicle (id)');
        $this->addSql('ALTER TABLE international_vehicle ADD CONSTRAINT FK_C2B94703430BF745 FOREIGN KEY (survey_response_id) REFERENCES international_survey_response (id)');
        $this->addSql('ALTER TABLE passcode_user ADD CONSTRAINT FK_21B8B82EF2CB3FAC FOREIGN KEY (domestic_survey_id) REFERENCES domestic_survey (id)');
        $this->addSql('ALTER TABLE passcode_user ADD CONSTRAINT FK_21B8B82EBAD517F8 FOREIGN KEY (international_survey_id) REFERENCES international_survey (id)');
        $this->addSql('ALTER TABLE passcode_user ADD CONSTRAINT FK_21B8B82EE4D0D43E FOREIGN KEY (pre_enquiry_id) REFERENCES pre_enquiry (id)');
        $this->addSql('ALTER TABLE pre_enquiry ADD CONSTRAINT FK_FD1E25C1979B1AD6 FOREIGN KEY (company_id) REFERENCES international_company (id)');
        $this->addSql('ALTER TABLE pre_enquiry_note ADD CONSTRAINT FK_66AB05E2E4D0D43E FOREIGN KEY (pre_enquiry_id) REFERENCES pre_enquiry (id)');
        $this->addSql('ALTER TABLE pre_enquiry_response ADD CONSTRAINT FK_907CD8F6E4D0D43E FOREIGN KEY (pre_enquiry_id) REFERENCES pre_enquiry (id)');
        $this->addSql('ALTER TABLE sampling_schema ADD CONSTRAINT FK_59D747C79C24126 FOREIGN KEY (day_id) REFERENCES sampling_schema_day (id)');

        foreach($this->getPortPairs() as [$ukPort, $foriegnPort]) {
            $this->addSql("INSERT INTO international_crossing_route (uk_port, foreign_port) VALUES (:ukPort, :foreignPort)", [
                'ukPort' => $ukPort,
                'foreignPort' => $foriegnPort,
            ]);
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE domestic_day_stop DROP FOREIGN KEY FK_232BAA6B9C24126');
        $this->addSql('ALTER TABLE domestic_day_summary DROP FOREIGN KEY FK_2DBD2CAF9C24126');
        $this->addSql('ALTER TABLE domestic_survey_note DROP FOREIGN KEY FK_1204C091B3FE509D');
        $this->addSql('ALTER TABLE domestic_survey_response DROP FOREIGN KEY FK_65962CB3FE509D');
        $this->addSql('ALTER TABLE passcode_user DROP FOREIGN KEY FK_21B8B82EF2CB3FAC');
        $this->addSql('ALTER TABLE domestic_day DROP FOREIGN KEY FK_E35E66FFFBF32840');
        $this->addSql('ALTER TABLE domestic_survey_response DROP FOREIGN KEY FK_65962C545317D1');
        $this->addSql('ALTER TABLE international_action DROP FOREIGN KEY FK_A835D57BF4C36F23');
        $this->addSql('ALTER TABLE international_survey DROP FOREIGN KEY FK_42A6C215979B1AD6');
        $this->addSql('ALTER TABLE pre_enquiry DROP FOREIGN KEY FK_FD1E25C1979B1AD6');
        $this->addSql('ALTER TABLE international_consignment DROP FOREIGN KEY FK_C28ABA766BDC8A9');
        $this->addSql('ALTER TABLE international_consignment DROP FOREIGN KEY FK_C28ABA7691586320');
        $this->addSql('ALTER TABLE international_survey_note DROP FOREIGN KEY FK_F966B825B3FE509D');
        $this->addSql('ALTER TABLE international_survey_response DROP FOREIGN KEY FK_A88444DCB3FE509D');
        $this->addSql('ALTER TABLE passcode_user DROP FOREIGN KEY FK_21B8B82EBAD517F8');
        $this->addSql('ALTER TABLE international_vehicle DROP FOREIGN KEY FK_C2B94703430BF745');
        $this->addSql('ALTER TABLE international_action DROP FOREIGN KEY FK_A835D57BA5BC2E0E');
        $this->addSql('ALTER TABLE international_consignment DROP FOREIGN KEY FK_C28ABA76A5BC2E0E');
        $this->addSql('ALTER TABLE international_stop DROP FOREIGN KEY FK_290BDE08A5BC2E0E');
        $this->addSql('ALTER TABLE international_trip DROP FOREIGN KEY FK_E60B3D85545317D1');
        $this->addSql('ALTER TABLE passcode_user DROP FOREIGN KEY FK_21B8B82EE4D0D43E');
        $this->addSql('ALTER TABLE pre_enquiry_note DROP FOREIGN KEY FK_66AB05E2E4D0D43E');
        $this->addSql('ALTER TABLE pre_enquiry_response DROP FOREIGN KEY FK_907CD8F6E4D0D43E');
        $this->addSql('ALTER TABLE international_company DROP FOREIGN KEY FK_9686AACA1B01E374');
        $this->addSql('ALTER TABLE sampling_schema DROP FOREIGN KEY FK_59D747C79C24126');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE domestic_day');
        $this->addSql('DROP TABLE domestic_day_stop');
        $this->addSql('DROP TABLE domestic_day_summary');
        $this->addSql('DROP TABLE domestic_survey');
        $this->addSql('DROP TABLE domestic_survey_note');
        $this->addSql('DROP TABLE domestic_survey_response');
        $this->addSql('DROP TABLE domestic_vehicle');
        $this->addSql('DROP TABLE international_action');
        $this->addSql('DROP TABLE international_company');
        $this->addSql('DROP TABLE international_consignment');
        $this->addSql('DROP TABLE international_crossing_route');
        $this->addSql('DROP TABLE international_stop');
        $this->addSql('DROP TABLE international_survey');
        $this->addSql('DROP TABLE international_survey_note');
        $this->addSql('DROP TABLE international_survey_response');
        $this->addSql('DROP TABLE international_trip');
        $this->addSql('DROP TABLE international_vehicle');
        $this->addSql('DROP TABLE maintenance_lock');
        $this->addSql('DROP TABLE maintenance_warning');
        $this->addSql('DROP TABLE passcode_user');
        $this->addSql('DROP TABLE pre_enquiry');
        $this->addSql('DROP TABLE pre_enquiry_note');
        $this->addSql('DROP TABLE pre_enquiry_response');
        $this->addSql('DROP TABLE sampling_group');
        $this->addSql('DROP TABLE sampling_schema');
        $this->addSql('DROP TABLE sampling_schema_day');

        $this->addSql('DROP TABLE sessions');
        $this->addSql('DROP TABLE messenger_messages');
    }

    /**
     * @return string[][]
     */
    protected function getPortPairs(): array
    {
        return [
            ["Aberdeen", "Stavanger"],
            ["Bristol", "Dublin"],
            ["Cairnryan", "Belfast"],
            ["Cairnryan", "Larne"],
            ["Channel Tunnel", "Channel Tunnel"],
            ["Dagenham", "Vlissingen"],
            ["Dover", "Calais"],
            ["Dover", "Dunkirk"],
            ["Felixstowe", "Vlaardingen"],
            ["Fishguard", "Rosslare"],
            ["Fleetwood", "Larne"],
            ["Harwich", "Cuxhaven"],
            ["Harwich", "Esbjerg"],
            ["Harwich", "Europoort"],
            ["Harwich", "Hook of Holland"],
            ["Harwich", "Paldiski"],
            ["Harwich", "Turku"],
            ["Heysham", "Belfast"],
            ["Heysham", "Dublin"],
            ["Heysham", "Larne"],
            ["Heysham", "Warrenpoint"],
            ["Holyhead", "Dublin"],
            ["Holyhead", "Dun Laoghaire"],
            ["Hull", "Europoort"],
            ["Hull", "Hamina"],
            ["Hull", "Helsinki"],
            ["Hull", "Kotka"],
            ["Hull", "Rauma"],
            ["Hull", "Santander"],
            ["Hull", "Turku"],
            ["Hull", "Zeebrugge"],
            ["Immingham", "Bergen"],
            ["Immingham", "Brevik"],
            ["Immingham", "Cuxhaven"],
            ["Immingham", "Esbjerg"],
            ["Immingham", "Gothenburg"],
            ["Immingham", "Helsinki"],
            ["Immingham", "Kotka"],
            ["Immingham", "Kristiansand"],
            ["Immingham", "Rauma"],
            ["Immingham", "Rotterdam"],
            ["Immingham", "Stavanger"],
            ["Immingham", "Vlaardingen"],
            ["Ipswich", "Rotterdam"],
            ["Killingholme", "Hook of Holland"],
            ["Killingholme", "Rotterdam"],
            ["Killingholme", "Vlaardingen"],
            ["Killingholme", "Zeebrugge"],
            ["Liverpool", "Belfast"],
            ["Liverpool", "Dublin"],
            ["Newhaven", "Dieppe"],
            ["Pembroke", "Rosslare"],
            ["Plymouth", "Cherbourg"],
            ["Plymouth", "Roscoff"],
            ["Plymouth", "Santander"],
            ["Plymouth", "St Malo"],
            ["Poole", "Bilbao"],
            ["Poole", "Caen"],
            ["Poole", "Cherbourg"],
            ["Poole", "Santander"],
            ["Poole", "St Malo"],
            ["Portsmouth", "Bilbao"],
            ["Portsmouth", "Caen"],
            ["Portsmouth", "Cherbourg"],
            ["Portsmouth", "Le Havre"],
            ["Portsmouth", "Santander"],
            ["Portsmouth", "St Malo"],
            ["Portsmouth", "Zeebrugge"],
            ["Purfleet", "Rotterdam"],
            ["Purfleet", "Zeebrugge"],
            ["Ramsgate", "Ostend"],
            ["Rosyth", "Zeebrugge"],
            ["Stranraer", "Belfast"],
            ["Swansea", "Cork"],
            ["Teesport", "Europoort"],
            ["Teesport", "Zeebrugge"],
            ["Tilbury", "Antwerp"],
            ["Tilbury", "Bilbao"],
            ["Tilbury", "Calais"],
            ["Tilbury", "Gothenburg"],
            ["Tilbury", "Hamina"],
            ["Tilbury", "Hanko"],
            ["Tilbury", "Helsinki"],
            ["Tilbury", "Kotka"],
            ["Tilbury", "Paldiski"],
            ["Tilbury", "Rauma"],
            ["Tilbury", "St Petersburg"],
            ["Tilbury", "Zeebrugge"],
            ["Troon", "Larne"],
            ["Tynemouth", "Ijmuiden"],
        ];
    }
}
