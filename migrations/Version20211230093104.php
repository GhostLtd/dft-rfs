<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20211230093104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix non-contiguous day stops numbering';
    }

    public function up(Schema $schema): void
    {
        $daysWithMisnumberedStops = $this->connection->fetchAllAssociative("SELECT ds.day_id, MAX(ds.number) AS maxNumber, COUNT(ds.id) as cnt FROM domestic_day_stop ds GROUP BY ds.day_id HAVING maxNumber <> cnt");
        $dayIds = array_map(fn(array $data) => $data['day_id'], $daysWithMisnumberedStops);

        $misnumberedStops = $this->connection->fetchAllAssociative("SELECT ds.id, ds.day_id, ds.number FROM domestic_day_stop ds WHERE ds.day_id IN (:dayIds) ORDER BY ds.day_id, ds.number", ['dayIds' => $dayIds], ['dayIds' => Connection::PARAM_STR_ARRAY]);

        $currentDay = null;
        $number = 1;

        foreach($misnumberedStops as $data) {
            $id = $data['id'];
            $dayId = $data['day_id'];

            if ($dayId !== $currentDay) {
                $currentDay = $dayId;
                $number = 1;
            }

            $this->addSql('UPDATE domestic_day_stop ds SET ds.number = :number WHERE ds.id = :id', [
                'number' => $number++,
                'id' => $id,
            ]);
        }
    }

    public function down(Schema $schema): void
    {
    }
}
