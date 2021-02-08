<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210204161649 extends AbstractMigration
{
    const COUNTRIES = ['GB','FR','BE','NL','DE','IE','IT','ES','CH','LU'];

    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE international_action ADD country_other VARCHAR(255) DEFAULT NULL, CHANGE country country VARCHAR(2) DEFAULT NULL');

        $placeholders = array_map(fn($x) => "country_{$x}", range(1, count(self::COUNTRIES)));
        $params = array_combine($placeholders, self::COUNTRIES);
        $placeholders = join(', ', array_map(fn($x) => ":{$x}", $placeholders));

        $this->addSql("UPDATE international_action SET country_other=country, country=null WHERE country NOT IN ({$placeholders})", $params);
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE international_action SET country=country_other WHERE country IS NULL');
        $this->addSql('ALTER TABLE international_action DROP country_other, CHANGE country country VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
