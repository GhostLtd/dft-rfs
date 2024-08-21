<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\AbortMigration;
use Symfony\Component\Uid\Uuid;

final class Version20231117104749 extends AbstractMigration
{
    public function getDescription(): string
    {
        // N.B. Two separate migrations makes it easier to verify that this migration is doing exactly what it should!
        return 'RoRo: OperationGroup data migrations';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection->getNativeConnection();

        if (!$conn instanceof \PDO) {
            throw new AbortMigration('Unexpected connection class');
        }

        $this->createOperatorGroup('Brittany Ferries');
        $this->createOperatorGroup('DFDS Seaways');
        $this->createOperatorGroup('Stena Line');

        $operatorIdMap = $this->getOperatorIdMap($conn);
        $routeIdMap = $this->getRouteIdMap($conn);

        $operatorId = $this->createOperator('Stena Line - Irish', 26);

        $this->moveRouteToOperator($routeIdMap['Heysham - Belfast'] ?? null, $operatorIdMap['Stena Line - HQ'] ?? null, $operatorId);
        $this->moveRouteToOperator($routeIdMap['Liverpool - Belfast'] ?? null, $operatorIdMap['Stena Line - HQ'] ?? null, $operatorId);
    }

    public function down(Schema $schema): void
    {
        $conn = $this->connection->getNativeConnection();

        if (!$conn instanceof \PDO) {
            throw new AbortMigration('Unexpected connection class');
        }

        $operatorIdMap = $this->getOperatorIdMap($conn);
        $routeIdMap = $this->getRouteIdMap($conn);

        $this->moveRouteToOperator($routeIdMap['Heysham - Belfast'] ?? null, $operatorIdMap['Stena Line - Irish'] ?? null, $operatorIdMap['Stena Line - HQ'] ?? null);
        $this->moveRouteToOperator($routeIdMap['Liverpool - Belfast'] ?? null, $operatorIdMap['Stena Line - Irish'] ?? null, $operatorIdMap['Stena Line - HQ'] ?? null);

        $this->deleteOperator($operatorIdMap['Stena Line - Irish']);

        $this->deleteOperatorGroup('Brittany Ferries');
        $this->deleteOperatorGroup('DFDS Seaways');
        $this->deleteOperatorGroup('Stena Line');
    }


    public function getOperatorIdMap(\PDO $conn): array
    {
        $operatorIdMap = [];

        $operators = $conn->query('SELECT id, name FROM roro_operator')->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($operators as $operator) {
            $operatorIdMap[$operator['name']] = $operator['id'];
        }

        return $operatorIdMap;
    }

    public function getRouteIdMap(\PDO $conn): array
    {
        $routeIdMap = [];

        $routes = $conn->query(<<<EOQ
SELECT r.id, up.name AS uk_port_name, fp.name AS foreign_port_name FROM route r 
JOIN route_uk_port up ON r.uk_port_id = up.id
JOIN route_foreign_port fp ON r.foreign_port_id = fp.id
EOQ)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($routes as $route) {
            $ukPort = $route['uk_port_name'];
            $foreignPort = $route['foreign_port_name'];

            $routeIdMap["{$ukPort} - {$foreignPort}"] = $route['id'];
        }

        return $routeIdMap;
    }

    public function createOperator(string $name, int $code): string
    {
        $id = Uuid::v4()->toRfc4122();

        $this->addSql("INSERT INTO roro_operator (id, name, code, is_active) VALUES (:id, :name, :code, :is_active)", [
            'id' => $id,
            'name' => $name,
            'code' => $code,
            'is_active' => 1,
        ]);

        return $id;
    }

    public function deleteOperator(string $operatorId): void
    {
        $this->addSql('DELETE FROM roro_operator WHERE id = :operatorId', ['operatorId' => $operatorId]);
    }

    public function createOperatorGroup(string $groupName): string
    {
        $id = Uuid::v4()->toRfc4122();

        $this->addSql('INSERT INTO roro_operator_group (id, name) VALUES (:id, :groupName)', [
            'id' => $id,
            'groupName' => $groupName
        ]);

        return $id;
    }

    public function deleteOperatorGroup(string $groupName): void
    {
        $this->addSql('DELETE FROM roro_operator_group WHERE name = :groupName', [
            'groupName' => $groupName
        ]);
    }

    public function moveRouteToOperator(?string $routeId, ?string $oldOperatorId, ?string $newOperatorId): void
    {
        if (!$routeId || !$oldOperatorId || !$newOperatorId) {
            // Skip for non-existent route / operator
            return;
        }

        foreach(['roro_operator_route', 'roro_survey'] as $table) {
            $this->addSql("UPDATE {$table} SET operator_id = :newOperatorId WHERE operator_id = :oldOperatorId AND route_id = :routeId", [
                'routeId' => $routeId,
                'oldOperatorId' => $oldOperatorId,
                'newOperatorId' => $newOperatorId,
            ]);
        }
    }
}
