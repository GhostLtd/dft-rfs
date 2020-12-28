<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201228135034 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action (id INT AUTO_INCREMENT NOT NULL, loading_action_id INT DEFAULT NULL, number SMALLINT NOT NULL, name VARCHAR(255) NOT NULL, country VARCHAR(255) NOT NULL, goods_description VARCHAR(255) DEFAULT NULL, goods_description_other VARCHAR(255) DEFAULT NULL, weight_of_goods INT NOT NULL, hazardous_goods_code VARCHAR(5) DEFAULT NULL, cargo_type_code VARCHAR(4) DEFAULT NULL, loading TINYINT(1) NOT NULL, INDEX IDX_47CC8C92F4C36F23 (loading_action_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92F4C36F23 FOREIGN KEY (loading_action_id) REFERENCES action (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92F4C36F23');
        $this->addSql('DROP TABLE action');
    }
}
