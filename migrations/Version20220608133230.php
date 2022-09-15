<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220608133230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add separate table for Notify Api Responses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE domestic_survey_notify_api_responses (survey_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', notify_api_response_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', INDEX IDX_C045C15B3FE509D (survey_id), UNIQUE INDEX UNIQ_C045C15D6C71CEF (notify_api_response_id), PRIMARY KEY(survey_id, notify_api_response_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE international_survey_notify_api_responses (survey_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', notify_api_response_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', INDEX IDX_98E6A5C0B3FE509D (survey_id), UNIQUE INDEX UNIQ_98E6A5C0D6C71CEF (notify_api_response_id), PRIMARY KEY(survey_id, notify_api_response_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notify_api_response (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', event_name VARCHAR(16) NOT NULL, message_class VARCHAR(255) NOT NULL, endpoint VARCHAR(255) NOT NULL, data LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', timestamp DATETIME NOT NULL, recipient_hash VARCHAR(64) NOT NULL, recipient VARCHAR(1024) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pre_enquiry_notify_api_responses (survey_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', notify_api_response_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', INDEX IDX_2F9F5B6DB3FE509D (survey_id), UNIQUE INDEX UNIQ_2F9F5B6DD6C71CEF (notify_api_response_id), PRIMARY KEY(survey_id, notify_api_response_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE domestic_survey_notify_api_responses ADD CONSTRAINT FK_C045C15B3FE509D FOREIGN KEY (survey_id) REFERENCES domestic_survey (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE domestic_survey_notify_api_responses ADD CONSTRAINT FK_C045C15D6C71CEF FOREIGN KEY (notify_api_response_id) REFERENCES notify_api_response (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE international_survey_notify_api_responses ADD CONSTRAINT FK_98E6A5C0B3FE509D FOREIGN KEY (survey_id) REFERENCES international_survey (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE international_survey_notify_api_responses ADD CONSTRAINT FK_98E6A5C0D6C71CEF FOREIGN KEY (notify_api_response_id) REFERENCES notify_api_response (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pre_enquiry_notify_api_responses ADD CONSTRAINT FK_2F9F5B6DB3FE509D FOREIGN KEY (survey_id) REFERENCES pre_enquiry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE pre_enquiry_notify_api_responses ADD CONSTRAINT FK_2F9F5B6DD6C71CEF FOREIGN KEY (notify_api_response_id) REFERENCES notify_api_response (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_survey_notify_api_responses DROP FOREIGN KEY FK_C045C15D6C71CEF');
        $this->addSql('ALTER TABLE international_survey_notify_api_responses DROP FOREIGN KEY FK_98E6A5C0D6C71CEF');
        $this->addSql('ALTER TABLE pre_enquiry_notify_api_responses DROP FOREIGN KEY FK_2F9F5B6DD6C71CEF');
        $this->addSql('DROP TABLE domestic_survey_notify_api_responses');
        $this->addSql('DROP TABLE international_survey_notify_api_responses');
        $this->addSql('DROP TABLE notify_api_response');
        $this->addSql('DROP TABLE pre_enquiry_notify_api_responses');
    }
}
