<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251106095454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove old LCNI tables. Trim leading and trailing white space from existing company names';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE international_notification_interception_company_name DROP FOREIGN KEY FK_63370BEE479140CD");
        $this->addSql("DROP TABLE domestic_notification_interception");
        $this->addSql("DROP TABLE international_notification_interception");
        $this->addSql("DROP TABLE international_notification_interception_company_name");
        $this->addSql("UPDATE notification_interception_company_name SET name = REGEXP_REPLACE(name, '^[[:space:]]+', '')");
        $this->addSql("UPDATE notification_interception_company_name SET name = REGEXP_REPLACE(name, '[[:space:]]+$', '')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("CREATE TABLE domestic_notification_interception (id INT AUTO_INCREMENT NOT NULL, address_line VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, emails VARCHAR(1024) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_3C8854EFAC58641D (address_line), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' ");
        $this->addSql("CREATE TABLE international_notification_interception (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, primary_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, emails VARCHAR(1024) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_2D0B093FF7B021CE (primary_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''");
        $this->addSql("CREATE TABLE international_notification_interception_company_name (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, notification_interception_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_63370BEE5E237E06 (name), INDEX IDX_63370BEE479140CD (notification_interception_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = ''");
        $this->addSql("ALTER TABLE international_notification_interception_company_name ADD CONSTRAINT FK_63370BEE479140CD FOREIGN KEY (notification_interception_id) REFERENCES international_notification_interception (id) ON UPDATE NO ACTION ON DELETE NO ACTION");
    }
}
