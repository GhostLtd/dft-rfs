<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230103130303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Surveys: Rename notified_date to invitation_sent_date. Add latest_manual_reminder_sent_date.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_survey CHANGE notified_date invitation_sent_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey CHANGE notified_date invitation_sent_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry CHANGE notified_date invitation_sent_date DATETIME DEFAULT NULL');

        $this->addSql('ALTER TABLE domestic_survey ADD latest_manual_reminder_sent_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey ADD latest_manual_reminder_sent_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry ADD latest_manual_reminder_sent_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_survey CHANGE invitation_sent_date notified_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey CHANGE invitation_sent_date notified_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry CHANGE invitation_sent_date notified_date DATETIME DEFAULT NULL');

        $this->addSql('ALTER TABLE domestic_survey DROP latest_manual_reminder_sent_date');
        $this->addSql('ALTER TABLE international_survey DROP latest_manual_reminder_sent_date');
        $this->addSql('ALTER TABLE pre_enquiry DROP latest_manual_reminder_sent_date');
    }
}
