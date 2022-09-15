<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220607105507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Entity changes to cope with multiple invitation_emails';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_survey CHANGE invitation_email invitation_emails VARCHAR(1024) DEFAULT NULL');
        $this->addSql('ALTER TABLE international_survey CHANGE invitation_email invitation_emails VARCHAR(1024) DEFAULT NULL');
        $this->addSql('ALTER TABLE pre_enquiry CHANGE invitation_email invitation_emails VARCHAR(1024) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_survey CHANGE invitation_emails invitation_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE international_survey CHANGE invitation_emails invitation_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE pre_enquiry CHANGE invitation_emails invitation_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
