<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Domestic\Survey as DomesticSurvey;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\Uid\Uuid;

final class Version20210929101841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the missing audit_log counterparts that should have been created to accompany the state changes of migration Version20210722103600';
    }

    public function getUsername(): string
    {
        return str_replace('DoctrineMigrations\Version', 'migration-', self::class);
    }

    public function up(Schema $schema): void
    {
        $rows = $this->connection->executeQuery(<<<EOQ
SELECT s.id, s.state, a.timestamp, a.data FROM audit_log a
INNER JOIN domestic_survey s ON a.entity_id = s.id
INNER JOIN (
    SELECT entity_id, MAX(timestamp) AS timestamp
    FROM audit_log
    WHERE category = "survey-state"
    GROUP BY entity_id
) m ON m.entity_id = a.entity_id AND m.timestamp = a.timestamp
WHERE a.category = "survey-state"
AND s.state = "reissued"
AND a.data LIKE "%""to"":""rejected""%"
EOQ)->fetchAllAssociative();

        $problematicMigrationDate = '2021-08-26 13:25:06';
        $username = $this->getUsername();

        foreach ($rows as $row) {
            $this->addSql('INSERT INTO audit_log SET id=:id,category=:category,username=:username, entity_id=:entity_id, entity_class=:class, timestamp=:timestamp, data=:data', [
                'id' => Uuid::v4()->toRfc4122(),
                'category' => 'survey-state',
                'username' => $username,
                'entity_id' => $row['id'],
                'class' => DomesticSurvey::class,
                'timestamp' => $problematicMigrationDate,
                'data' => '{"from":"rejected","to":"reissued"}',
            ]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM audit_log WHERE username=:username', [
            'username' => $this->getUsername(),
        ]);
    }
}
