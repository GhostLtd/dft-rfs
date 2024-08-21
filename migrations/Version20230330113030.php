<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

final class Version20230330113030 extends AbstractMigration
{
    const COUNTRY_CODES = [
        'AL', // Albania
        'AT', // Austria
        'BY', // Belarus
        'BE', // Belgium
        'BA', // Bosnia and Herzegovina
        'BG', // Bulgaria
        'HR', // Croatia
        'CY', // Cyprus
        'CZ', // Czechia
        'DK', // Denmark
        'EE', // Estonia
        'FI', // Finland
        'FR', // France
        'GE', // Georgia
        'DE', // Germany
        'GR', // Greece
        'HU', // Hungary
        'IS', // Iceland
        'IE', // Ireland
        'IT', // Italy
        'XK', // Kosovo??
        'LV', // Latvia
        'LT', // Lithuania
        'LU', // Luxembourg
        'MT', // Malta
        'MD', // Moldova (the Republic of)
        'ME', // Montenegro
        'NL', // Netherlands
        'MK', // North Macedonia
        'NO', // Norway
        'PL', // Poland
        'PT', // Portugal
        'RO', // Romania
        'RU', // Russian Federation
        'RS', // Serbia
        'SK', // Slovakia
        'SI', // Slovenia
        'ES', // Spain
        'SE', // Sweden
        'CH', // Switzerland
        'TR', // Turkey
        'UA', // Ukraine
        'GB', // United Kingdom
    ];

    public function getDescription(): string
    {
        return 'updates for RoRo surveys (country, vehicle_counts)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE roro_country DROP name');
        $this->addSql('ALTER TABLE roro_survey ADD is_active_for_period TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE roro_country_vehicle_count ADD other_code VARCHAR(16) DEFAULT NULL, CHANGE country_code country_code VARCHAR(2) DEFAULT NULL, CHANGE vehicle_count vehicle_count INT DEFAULT NULL');

        foreach (self::COUNTRY_CODES as $countryCode) {
            $this->addSql('INSERT INTO roro_country(id, code) VALUES(:id, :country)', [
                'id' => Uuid::v1(),
                'country' => $countryCode,
            ]);
        }

        // rename roro_vehicle_count table (and indexes)
        $this->addSql('ALTER TABLE roro_country_vehicle_count DROP FOREIGN KEY FK_3619025FB3FE509D');
        $this->addSql('RENAME TABLE roro_country_vehicle_count TO roro_vehicle_count');
        $this->addSql('DROP INDEX idx_57321857b3fe509d ON roro_vehicle_count');
        $this->addSql('CREATE INDEX IDX_C92DB93EB3FE509D ON roro_vehicle_count (survey_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C92DB93EB3FE509DF026BB7C1E51664A ON roro_vehicle_count (survey_id, country_code, other_code)');
        $this->addSql('ALTER TABLE roro_vehicle_count ADD CONSTRAINT FK_C92DB93EB3FE509D FOREIGN KEY (survey_id) REFERENCES roro_survey (id)');

    }

    public function down(Schema $schema): void
    {
        // undo rename of roro_vehicle_count table (and indexes)
        $this->addSql('ALTER TABLE roro_vehicle_count DROP FOREIGN KEY FK_C92DB93EB3FE509D');
        $this->addSql('RENAME TABLE roro_vehicle_count TO roro_country_vehicle_count');
        $this->addSql('DROP INDEX idx_c92db93eb3fe509d ON roro_country_vehicle_count');
        $this->addSql('CREATE INDEX IDX_57321857B3FE509D ON roro_country_vehicle_count (survey_id)');
        $this->addSql('DROP INDEX uniq_c92db93eb3fe509df026bb7c1e51664a ON roro_country_vehicle_count');
        $this->addSql('ALTER TABLE roro_country_vehicle_count ADD CONSTRAINT FK_3619025FB3FE509D FOREIGN KEY (survey_id) REFERENCES roro_survey (id)');
        $this->addSql('DELETE FROM roro_country WHERE true=true');
        $this->addSql('ALTER TABLE roro_country ADD name VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE roro_country_vehicle_count DROP other_code, CHANGE country_code country_code VARCHAR(8) NOT NULL, CHANGE vehicle_count vehicle_count INT NOT NULL');

        $this->addSql('ALTER TABLE roro_survey DROP is_active_for_period');
    }
}
