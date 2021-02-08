<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210208102729 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE international_crossing_route (id INT AUTO_INCREMENT NOT NULL, uk_port VARCHAR(255) NOT NULL, foreign_port VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        foreach($this->getPortPairs() as [$ukPort, $foriegnPort]) {
            $this->addSql("INSERT INTO international_crossing_route (uk_port, foreign_port) VALUES (:ukPort, :foreignPort)", [
                'ukPort' => $ukPort,
                'foreignPort' => $foriegnPort,
            ]);
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE international_crossing_route');
    }

    /**
     * @return \string[][]
     */
    protected function getPortPairs(): array
    {
        return [
            ["Aberdeen", "Stavanger"],
            ["Bristol", "Dublin"],
            ["Cairnryan", "Belfast"],
            ["Cairnryan", "Larne"],
            ["Channel Tunnel", "Channel Tunnel"],
            ["Dagenham", "Vlissingen"],
            ["Dover", "Calais"],
            ["Dover", "Dunkirk"],
            ["Felixstowe", "Vlaardingen"],
            ["Fishguard", "Rosslare"],
            ["Fleetwood", "Larne"],
            ["Harwich", "Cuxhaven"],
            ["Harwich", "Esbjerg"],
            ["Harwich", "Europoort"],
            ["Harwich", "Hook of Holland"],
            ["Harwich", "Paldiski"],
            ["Harwich", "Turku"],
            ["Heysham", "Belfast"],
            ["Heysham", "Dublin"],
            ["Heysham", "Larne"],
            ["Heysham", "Warrenpoint"],
            ["Holyhead", "Dublin"],
            ["Holyhead", "Dun Laoghaire"],
            ["Hull", "Europoort"],
            ["Hull", "Hamina"],
            ["Hull", "Helsinki"],
            ["Hull", "Kotka"],
            ["Hull", "Rauma"],
            ["Hull", "Santander"],
            ["Hull", "Turku"],
            ["Hull", "Zeebrugge"],
            ["Immingham", "Bergen"],
            ["Immingham", "Brevik"],
            ["Immingham", "Cuxhaven"],
            ["Immingham", "Esbjerg"],
            ["Immingham", "Gothenburg"],
            ["Immingham", "Helsinki"],
            ["Immingham", "Kotka"],
            ["Immingham", "Kristiansand"],
            ["Immingham", "Rauma"],
            ["Immingham", "Rotterdam"],
            ["Immingham", "Stavanger"],
            ["Immingham", "Vlaardingen"],
            ["Ipswich", "Rotterdam"],
            ["Killingholme", "Hook of Holland"],
            ["Killingholme", "Rotterdam"],
            ["Killingholme", "Vlaardingen"],
            ["Killingholme", "Zeebrugge"],
            ["Liverpool", "Belfast"],
            ["Liverpool", "Dublin"],
            ["Newhaven", "Dieppe"],
            ["Pembroke", "Rosslare"],
            ["Plymouth", "Cherbourg"],
            ["Plymouth", "Roscoff"],
            ["Plymouth", "Santander"],
            ["Plymouth", "St Malo"],
            ["Poole", "Bilbao"],
            ["Poole", "Caen"],
            ["Poole", "Cherbourg"],
            ["Poole", "Santander"],
            ["Poole", "St Malo"],
            ["Portsmouth", "Bilbao"],
            ["Portsmouth", "Caen"],
            ["Portsmouth", "Cherbourg"],
            ["Portsmouth", "Le Havre"],
            ["Portsmouth", "Santander"],
            ["Portsmouth", "St Malo"],
            ["Portsmouth", "Zeebrugge"],
            ["Purfleet", "Rotterdam"],
            ["Purfleet", "Zeebrugge"],
            ["Ramsgate", "Ostend"],
            ["Rosyth", "Zeebrugge"],
            ["Stranraer", "Belfast"],
            ["Swansea", "Cork"],
            ["Teesport", "Europoort"],
            ["Teesport", "Zeebrugge"],
            ["Tilbury", "Antwerp"],
            ["Tilbury", "Bilbao"],
            ["Tilbury", "Calais"],
            ["Tilbury", "Gothenburg"],
            ["Tilbury", "Hamina"],
            ["Tilbury", "Hanko"],
            ["Tilbury", "Helsinki"],
            ["Tilbury", "Kotka"],
            ["Tilbury", "Paldiski"],
            ["Tilbury", "Rauma"],
            ["Tilbury", "St Petersburg"],
            ["Tilbury", "Zeebrugge"],
            ["Troon", "Larne"],
            ["Tynemouth", "Ijmuiden"],
        ];
    }
}
