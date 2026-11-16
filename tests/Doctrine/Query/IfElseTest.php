<?php

namespace App\Tests\Doctrine\Query;

use App\Entity\Utility\MaintenanceWarning;
use App\Tests\DataFixtures\MaintenanceWarningFixtures;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * IF() must compile to MySQL's own IF() in production (and in the MySQL test suite), but to
 * CASE WHEN on SQLite, which has no IF().
 */
class IfElseTest extends KernelTestCase
{
    private const string DQL = 'SELECT IF(w.id IS NULL, 0, 2) AS a, IF(w.id IS NULL, NULL, 1) AS b FROM '
        . MaintenanceWarning::class . ' w';

    public static function dataPlatforms(): array
    {
        return [
            'mysql' => [
                ['driver' => 'pdo_mysql', 'serverVersion' => '8.0.18'],
                'IF(m0_.id IS NULL, 0, 2)',
                'IF(m0_.id IS NULL, NULL, 1)',
            ],
            'sqlite' => [
                ['driver' => 'pdo_sqlite', 'memory' => true],
                'CASE WHEN m0_.id IS NULL THEN 0 ELSE 2 END',
                'CASE WHEN m0_.id IS NULL THEN NULL ELSE 1 END',
            ],
        ];
    }

    #[DataProvider('dataPlatforms')]
    public function testCompilesForThePlatform(array $connectionParams, string $expectedA, string $expectedB): void
    {
        $sql = $this->createEntityManager($connectionParams)->createQuery(self::DQL)->getSQL();

        $this->assertStringContainsString($expectedA, $sql);
        $this->assertStringContainsString($expectedB, $sql);
    }

    public function testEvaluatesOnTheTestDatabase(): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([MaintenanceWarningFixtures::class]);

        $rows = $container->get(EntityManagerInterface::class)->createQuery(self::DQL)->getArrayResult();

        $this->assertSame([['a' => 2, 'b' => 1]], array_map(
            fn(array $row) => ['a' => (int) $row['a'], 'b' => (int) $row['b']],
            $rows,
        ));
    }

    /**
     * The app's ORM configuration (mappings, DQL functions, ID generators) on a connection for the given platform.
     * getSQL() only needs the platform, so nothing connects to a server (string literals would: they're quoted
     * by the connection, hence the numeric literals in the DQL).
     */
    private function createEntityManager(array $connectionParams): EntityManager
    {
        static::bootKernel();
        $appEntityManager = static::getContainer()->get(EntityManagerInterface::class);

        return new EntityManager(
            DriverManager::getConnection($connectionParams),
            $appEntityManager->getConfiguration(),
            $appEntityManager->getEventManager(),
        );
    }
}
