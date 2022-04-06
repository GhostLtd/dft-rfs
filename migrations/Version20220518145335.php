<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220518145335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add feedback table/links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE feedback (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', experience_rating VARCHAR(50) NOT NULL, has_completed_paper_survey TINYINT(1) NOT NULL, comparison_rating VARCHAR(50) DEFAULT NULL, time_to_complete VARCHAR(255) DEFAULT NULL, had_issues VARCHAR(255) NOT NULL, issue_details LONGTEXT DEFAULT NULL, comments LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, exported_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE domestic_survey ADD feedback_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE domestic_survey ADD CONSTRAINT FK_C3309B36D249A887 FOREIGN KEY (feedback_id) REFERENCES feedback (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C3309B36D249A887 ON domestic_survey (feedback_id)');
        $this->addSql('ALTER TABLE international_survey ADD feedback_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE international_survey ADD CONSTRAINT FK_42A6C215D249A887 FOREIGN KEY (feedback_id) REFERENCES feedback (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_42A6C215D249A887 ON international_survey (feedback_id)');
        $this->addSql('ALTER TABLE pre_enquiry ADD feedback_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\'');
        $this->addSql('ALTER TABLE pre_enquiry ADD CONSTRAINT FK_FD1E25C1D249A887 FOREIGN KEY (feedback_id) REFERENCES feedback (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FD1E25C1D249A887 ON pre_enquiry (feedback_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE domestic_survey DROP FOREIGN KEY FK_C3309B36D249A887');
        $this->addSql('ALTER TABLE international_survey DROP FOREIGN KEY FK_42A6C215D249A887');
        $this->addSql('ALTER TABLE pre_enquiry DROP FOREIGN KEY FK_FD1E25C1D249A887');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('DROP INDEX UNIQ_C3309B36D249A887 ON domestic_survey');
        $this->addSql('ALTER TABLE domestic_survey DROP feedback_id');
        $this->addSql('DROP INDEX UNIQ_42A6C215D249A887 ON international_survey');
        $this->addSql('ALTER TABLE international_survey DROP feedback_id');
        $this->addSql('DROP INDEX UNIQ_FD1E25C1D249A887 ON pre_enquiry');
        $this->addSql('ALTER TABLE pre_enquiry DROP feedback_id');
    }
}
