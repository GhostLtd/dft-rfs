<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220119163308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update distance unit constants: kilometers -> kilometres';
    }

    public function up(Schema $schema): void
    {
        $this->addDistanceUnitMigrationSql('kilometers', 'kilometres');
    }

    public function down(Schema $schema): void
    {
        $this->addDistanceUnitMigrationSql('kilometres', 'kilometers');
    }

    protected function addDistanceUnitMigrationSql(string $changeFrom, string $changeTo): void
    {
        $params = [
            'changeFrom' => $changeFrom,
            'changeTo' => $changeTo,
        ];

        $this->addSql('UPDATE domestic_day_stop SET distance_travelled_unit = :changeTo WHERE distance_travelled_unit = :changeFrom', $params);
        $this->addSql('UPDATE domestic_day_summary SET distance_travelled_loaded_unit = :changeTo WHERE distance_travelled_loaded_unit = :changeFrom', $params);
        $this->addSql('UPDATE domestic_day_summary SET distance_travelled_unloaded_unit = :changeTo WHERE distance_travelled_unloaded_unit = :changeFrom', $params);
        $this->addSql('UPDATE international_trip SET round_trip_distance_unit = :changeTo WHERE round_trip_distance_unit = :changeFrom', $params);
    }
}
