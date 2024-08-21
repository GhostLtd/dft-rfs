<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20231115103731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoRo: Remove bookmarked routes feature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_user_route DROP FOREIGN KEY FK_CBE4F30634ECB4E6');
        $this->addSql('ALTER TABLE roro_user_route DROP FOREIGN KEY FK_CBE4F306B7EA4DAB');
        $this->addSql('DROP TABLE roro_user_route');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE roro_user_route (roro_user_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, route_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_CBE4F30634ECB4E6 (route_id), INDEX IDX_CBE4F306B7EA4DAB (roro_user_id), PRIMARY KEY(roro_user_id, route_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE roro_user_route ADD CONSTRAINT FK_CBE4F30634ECB4E6 FOREIGN KEY (route_id) REFERENCES route (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE roro_user_route ADD CONSTRAINT FK_CBE4F306B7EA4DAB FOREIGN KEY (roro_user_id) REFERENCES roro_user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
