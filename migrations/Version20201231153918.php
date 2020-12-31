<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201231153918 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE international_action (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', loading_action_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', trip_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', number SMALLINT NOT NULL, name VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, goods_description VARCHAR(255) DEFAULT NULL, goods_description_other VARCHAR(255) DEFAULT NULL, weight_of_goods INT NOT NULL, loading TINYINT(1) NOT NULL, hazardous_goods_code VARCHAR(5) DEFAULT NULL, cargo_type_code VARCHAR(4) DEFAULT NULL, INDEX IDX_A835D57BF4C36F23 (loading_action_id), INDEX IDX_A835D57BA5BC2E0E (trip_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE international_action ADD CONSTRAINT FK_A835D57BF4C36F23 FOREIGN KEY (loading_action_id) REFERENCES international_action (id)');
        $this->addSql('ALTER TABLE international_action ADD CONSTRAINT FK_A835D57BA5BC2E0E FOREIGN KEY (trip_id) REFERENCES international_trip (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_action DROP FOREIGN KEY FK_A835D57BF4C36F23');
        $this->addSql('DROP TABLE international_action');
    }
}
