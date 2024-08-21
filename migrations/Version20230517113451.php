<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230517113451 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoRo: Notifications related (reminder dates and api responses)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE roro_user_notify_api_responses (user_id CHAR(36) NOT NULL, notify_api_response_id CHAR(36) NOT NULL, INDEX IDX_1D8927DFA76ED395 (user_id), UNIQUE INDEX UNIQ_1D8927DFD6C71CEF (notify_api_response_id), PRIMARY KEY(user_id, notify_api_response_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE roro_user_notify_api_responses ADD CONSTRAINT FK_1D8927DFA76ED395 FOREIGN KEY (user_id) REFERENCES roro_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE roro_user_notify_api_responses ADD CONSTRAINT FK_1D8927DFD6C71CEF FOREIGN KEY (notify_api_response_id) REFERENCES notify_api_response (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE roro_survey ADD first_reminder_sent_date DATETIME DEFAULT NULL, ADD second_reminder_sent_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_user_notify_api_responses DROP FOREIGN KEY FK_1D8927DFA76ED395');
        $this->addSql('ALTER TABLE roro_user_notify_api_responses DROP FOREIGN KEY FK_1D8927DFD6C71CEF');
        $this->addSql('DROP TABLE roro_user_notify_api_responses');
        $this->addSql('ALTER TABLE roro_survey DROP first_reminder_sent_date, DROP second_reminder_sent_date');
    }
}
