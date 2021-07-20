<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210708104044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_consignment DROP FOREIGN KEY FK_C28ABA766BDC8A9');
        $this->addSql('ALTER TABLE international_consignment DROP FOREIGN KEY FK_C28ABA7691586320');
        $this->addSql('DROP TABLE international_consignment');
        $this->addSql('DROP TABLE international_stop');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE international_consignment (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', loading_stop_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', unloading_stop_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', trip_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', goods_description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, goods_description_other VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, weight_of_goods_carried INT NOT NULL, hazardous_goods_code VARCHAR(5) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, cargo_type_code VARCHAR(4) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_C28ABA766BDC8A9 (loading_stop_id), INDEX IDX_C28ABA7691586320 (unloading_stop_id), INDEX IDX_C28ABA76A5BC2E0E (trip_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE international_stop (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', trip_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, country VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, number SMALLINT NOT NULL, INDEX IDX_290BDE08A5BC2E0E (trip_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE international_consignment ADD CONSTRAINT FK_C28ABA766BDC8A9 FOREIGN KEY (loading_stop_id) REFERENCES international_stop (id)');
        $this->addSql('ALTER TABLE international_consignment ADD CONSTRAINT FK_C28ABA7691586320 FOREIGN KEY (unloading_stop_id) REFERENCES international_stop (id)');
        $this->addSql('ALTER TABLE international_consignment ADD CONSTRAINT FK_C28ABA76A5BC2E0E FOREIGN KEY (trip_id) REFERENCES international_trip (id)');
        $this->addSql('ALTER TABLE international_stop ADD CONSTRAINT FK_290BDE08A5BC2E0E FOREIGN KEY (trip_id) REFERENCES international_trip (id)');
    }
}
