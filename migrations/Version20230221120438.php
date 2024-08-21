<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

final class Version20230221120438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ports and routes list';
    }

    public function up(Schema $schema): void
    {
        $ukPortMapping = $this->addPortsAndGetNameToIdMap('route_uk_port', $this->getUkPorts());
        $foreignPortMapping = $this->addPortsAndGetNameToIdMap('route_foreign_port', $this->getForeignPorts());

        $results = $this->connection->iterateAssociative("SELECT r.uk_port, r.foreign_port FROM international_crossing_route r");

        $seenPorts = [];

        foreach($results as ['uk_port' => $ukPort, 'foreign_port' => $foreignPort]) {
            $seenPorts["$ukPort:$foreignPort"] = true;
            $this->addRoute($ukPortMapping[$ukPort], $foreignPortMapping[$foreignPort]);
        }

        // Extra routes requested by Darren - 2023/02/20 - 14:15
        $extraPorts = [
            ['Immingham', 'Europoort'],
            ['Killingholme', 'Europoort'],
            ['Sheerness', 'Calais'],
        ];

        foreach($extraPorts as [$ukPort, $foreignPort]) {
            if (!isset($seenPorts["$ukPort:$foreignPort"])) {
                $this->addRoute($ukPortMapping[$ukPort], $foreignPortMapping[$foreignPort]);
            }
        }

        $this->addSql('DROP TABLE international_crossing_route');
    }

    public function down(Schema $schema): void
    {
        $stmt = $this->connection->executeQuery(<<<EOQ
SELECT up.name AS uk_name, fp.name AS foreign_name 
FROM route r 
JOIN route_uk_port up on r.uk_port_id = up.id 
JOIN route_foreign_port fp on r.foreign_port_id = fp.id
EOQ);
        $ports = $stmt->fetchAllAssociative();

        $this->addSql('DELETE FROM route');
        $this->addSql('DELETE FROM route_uk_port');
        $this->addSql('DELETE FROM route_foreign_port');
        $this->addSql('CREATE TABLE international_crossing_route (id INT AUTO_INCREMENT NOT NULL, uk_port VARCHAR(255) NOT NULL, foreign_port VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        foreach($ports as $portNamePair) {
            $this->addSql('INSERT INTO international_crossing_route (uk_port, foreign_port) VALUES (:uk_name, :foreign_name)', $portNamePair);
        }
    }

    protected function addPortsAndGetNameToIdMap(string $table, array $ports): array
    {
        $mapping = [];

        foreach($ports as [$code, $name]) {
            $id = Uuid::v1();
            $mapping[$name] = $id;

            $this->addSql("INSERT INTO {$table} (id, name, code) VALUES (:id, :name, :code)", [
                'id' => $id,
                'name' => $name,
                'code' => $code,
            ]);
        }

        return $mapping;
    }

    protected function addRoute(mixed $ukPortId, mixed $foreignPortId): void
    {
        $this->addSql('INSERT INTO route (id, uk_port_id, foreign_port_id, is_active) VALUES (:id, :ukPortId, :foreignPortId, true)', [
            'id' => Uuid::v1(),
            'ukPortId' => $ukPortId,
            'foreignPortId' => $foreignPortId,
        ]);
    }

    protected function getUkPorts(): array
    {
        return [
            [48, "Aberdeen"],
            [49, "Bristol"],
            [10, "Cairnryan"],
            [11, "Channel Tunnel"],
            [45, "Chatham"],
            [12, "Dagenham"],
            [13, "Dartford"],
            [14, "Dover"],
            [15, "Felixstowe"],
            [16, "Fishguard"],
            [17, "Fleetwood"],
            [43, "Folkestone"],
            [18, "Grimsby"],
            [19, "Harwich"],
            [20, "Heysham"],
            [21, "Holyhead"],
            [22, "Hull"],
            [99, "ILB"],
            [23, "Immingham"],
            [24, "Ipswich"],
            [25, "Killingholme"],
            [26, "Liverpool"],
            [27, "Mostyn"],
            [28, "Newcastle"],
            [41, "Newhaven"],
            [30, "Pembroke"],
            [31, "Plymouth"],
            [32, "Poole"],
            [33, "Portsmouth"],
            [34, "Purfleet"],
            [35, "Ramsgate"],
            [36, "Rosyth"],
            [44, "Sheerness"],
            [42, "Southampton"],
            [37, "Stranraer"],
            [38, "Swansea"],
            [39, "Teesport"],
            [47, "Tilbury"],
            [40, "Troon"],
            [29, "Tynemouth"],
            [46, "Weymouth"],
        ];
    }

    protected function getForeignPorts(): array
    {
        return [
            [1, "Amsterdam"],
            [2, "Antwerp"],
            [3, "Belfast"],
            [4, "Bergen"],
            [5, "Bilbao"],
            [6, "Brevik"],
            [7, "Caen"],
            [8, "Calais"],
            [9, "Cherbourg"],
            [10, "Channel Tunnel"],
            [11, "Copenhagen"],
            [12, "Cork"],
            [13, "Cuxhaven"],
            [14, "Dublin"],
            [15, "Dun Laoghaire"],
            [16, "Dunkirk"],
            [17, "Esbjerg"],
            [18, "Europoort"],
            [19, "Gothenburg"],
            [20, "Hamburg"],
            [21, "Hamina"],
            [22, "Helsinki"],
            [23, "Hook of Holland"],
            [24, "Ijmuiden"],
            [25, "Kristiansand"],
            [26, "Larne"],
            [27, "Le Havre"],
            [29, "Ostend"],
            [30, "Roscoff"],
            [31, "Rosslare"],
            [32, "Rotterdam"],
            [33, "Santander"],
            [34, "Scheveningen"],
            [35, "St Malo"],
            [36, "Turku"],
            [37, "Vlissingen"],
            [38, "Warrenpoint"],
            [39, "Zeebrugge"],
            [40, "Dieppe"],
            [41, "Rostock"],
            [42, "Boulogne"],
            [43, "Rouen"],
            [44, "Paldiski"],
            [45, "Rauma"],
            [46, "Porto"],
            [47, "Vlaardingen"],
            [48, "Stavanger"],
            [49, "Haugesund"],
            [50, "Hanko"],
            [51, "Kotka"],
            [52, "St Petersburg"],
            [99, "ILB"],
        ];
    }
}
