<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230221120428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoRo entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE roro_country (id CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, code VARCHAR(8) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roro_operator (id CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, code SMALLINT NOT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roro_operator_route (operator_id CHAR(36) NOT NULL, route_id CHAR(36) NOT NULL, INDEX IDX_B49EC583584598A3 (operator_id), INDEX IDX_B49EC58334ECB4E6 (route_id), PRIMARY KEY(operator_id, route_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roro_survey (id CHAR(36) NOT NULL, operator_id CHAR(36) NOT NULL, route_id CHAR(36) NOT NULL, survey_period_start DATE NOT NULL, state VARCHAR(20) NOT NULL, INDEX IDX_F3BBFF66584598A3 (operator_id), INDEX IDX_F3BBFF6634ECB4E6 (route_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roro_user (id CHAR(36) NOT NULL, operator_id CHAR(36) NOT NULL, username VARCHAR(255) NOT NULL, last_login DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_A5566028F85E0677 (username), INDEX IDX_A5566028584598A3 (operator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roro_user_route (roro_user_id CHAR(36) NOT NULL, route_id CHAR(36) NOT NULL, INDEX IDX_CBE4F306B7EA4DAB (roro_user_id), INDEX IDX_CBE4F30634ECB4E6 (route_id), PRIMARY KEY(roro_user_id, route_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roro_country_vehicle_count (id CHAR(36) NOT NULL, survey_id CHAR(36) NOT NULL, country_code VARCHAR(8) NOT NULL, vehicle_count INT NOT NULL, INDEX IDX_57321857B3FE509D (survey_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE route (id CHAR(36) NOT NULL, uk_port_id CHAR(36) NOT NULL, foreign_port_id CHAR(36) NOT NULL, is_active TINYINT(1) NOT NULL, INDEX IDX_2C420794A5F2D5B (uk_port_id), INDEX IDX_2C42079EC8782F4 (foreign_port_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE route_foreign_port (id CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, code SMALLINT NOT NULL, UNIQUE INDEX UNIQ_9A8DC1DE5E237E06 (name), UNIQUE INDEX UNIQ_9A8DC1DE77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE route_uk_port (id CHAR(36) NOT NULL, name VARCHAR(255) NOT NULL, code SMALLINT NOT NULL, UNIQUE INDEX UNIQ_61CA58395E237E06 (name), UNIQUE INDEX UNIQ_61CA583977153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE roro_operator_route ADD CONSTRAINT FK_B49EC583584598A3 FOREIGN KEY (operator_id) REFERENCES roro_operator (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE roro_operator_route ADD CONSTRAINT FK_B49EC58334ECB4E6 FOREIGN KEY (route_id) REFERENCES route (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE roro_survey ADD CONSTRAINT FK_F3BBFF66584598A3 FOREIGN KEY (operator_id) REFERENCES roro_operator (id)');
        $this->addSql('ALTER TABLE roro_survey ADD CONSTRAINT FK_F3BBFF6634ECB4E6 FOREIGN KEY (route_id) REFERENCES route (id)');
        $this->addSql('ALTER TABLE roro_user ADD CONSTRAINT FK_A5566028584598A3 FOREIGN KEY (operator_id) REFERENCES roro_operator (id)');
        $this->addSql('ALTER TABLE roro_user_route ADD CONSTRAINT FK_CBE4F306B7EA4DAB FOREIGN KEY (roro_user_id) REFERENCES roro_user (id)');
        $this->addSql('ALTER TABLE roro_user_route ADD CONSTRAINT FK_CBE4F30634ECB4E6 FOREIGN KEY (route_id) REFERENCES route (id)');
        $this->addSql('ALTER TABLE roro_country_vehicle_count ADD CONSTRAINT FK_3619025FB3FE509D FOREIGN KEY (survey_id) REFERENCES roro_survey (id)');
        $this->addSql('ALTER TABLE route ADD CONSTRAINT FK_2C420794A5F2D5B FOREIGN KEY (uk_port_id) REFERENCES route_uk_port (id)');
        $this->addSql('ALTER TABLE route ADD CONSTRAINT FK_2C42079EC8782F4 FOREIGN KEY (foreign_port_id) REFERENCES route_foreign_port (id)');
        $this->addSql('CREATE UNIQUE INDEX roro_operation_route_date_index ON roro_survey (operator_id, route_id, survey_period_start)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX roro_operation_route_date_index ON roro_survey');
        $this->addSql('ALTER TABLE roro_operator_route DROP FOREIGN KEY FK_B49EC583584598A3');
        $this->addSql('ALTER TABLE roro_operator_route DROP FOREIGN KEY FK_B49EC58334ECB4E6');
        $this->addSql('ALTER TABLE roro_survey DROP FOREIGN KEY FK_F3BBFF66584598A3');
        $this->addSql('ALTER TABLE roro_survey DROP FOREIGN KEY FK_F3BBFF6634ECB4E6');
        $this->addSql('ALTER TABLE roro_user DROP FOREIGN KEY FK_A5566028584598A3');
        $this->addSql('ALTER TABLE roro_user_route DROP FOREIGN KEY FK_CBE4F306B7EA4DAB');
        $this->addSql('ALTER TABLE roro_user_route DROP FOREIGN KEY FK_CBE4F30634ECB4E6');
        $this->addSql('ALTER TABLE roro_country_vehicle_count DROP FOREIGN KEY FK_3619025FB3FE509D');
        $this->addSql('ALTER TABLE route DROP FOREIGN KEY FK_2C420794A5F2D5B');
        $this->addSql('ALTER TABLE route DROP FOREIGN KEY FK_2C42079EC8782F4');
        $this->addSql('DROP TABLE roro_country');
        $this->addSql('DROP TABLE roro_operator');
        $this->addSql('DROP TABLE roro_operator_route');
        $this->addSql('DROP TABLE roro_survey');
        $this->addSql('DROP TABLE roro_user');
        $this->addSql('DROP TABLE roro_user_route');
        $this->addSql('DROP TABLE roro_country_vehicle_count');
        $this->addSql('DROP TABLE route');
        $this->addSql('DROP TABLE route_foreign_port');
        $this->addSql('DROP TABLE route_uk_port');
    }
}
