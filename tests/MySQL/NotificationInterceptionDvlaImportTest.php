<?php

namespace App\Tests\MySQL;

use App\Controller\Admin\NotificationInterceptionDvlaImportController;
use App\Entity\NotificationInterception;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

/**
 * The DVLA import's database steps: clear the previous DVLA emails, merge in the new ones, then delete records left
 * with no emails at all. The last step is a multi-table DELETE ... JOIN (MySQL-only syntax, a syntax error on SQLite),
 * so this path can only be tested here. Parsing the upload is tested in NotificationInterceptionDvlaImportCsvTest.
 */
class NotificationInterceptionDvlaImportTest extends KernelTestCase
{
    use RunsOnMySql;

    private EntityManagerInterface $entityManager;
    private Connection $connection;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->connection = $container->get('doctrine.dbal.default_connection');
    }

    public function testImportReplacesDvlaEmailsAndRemovesRecordsLeftEmpty(): void
    {
        $dvlaOnly = $this->createRecord('Acme Ltd', dvla: 'old@acme.com');
        $csrgtToo = $this->createRecord('Beta Ltd', dvla: 'old@beta.com', csrgt: 'csrgt@beta.com');
        $reimported = $this->createRecord('Gamma Ltd', dvla: 'old@gamma.com');
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->runImport([
            ['GAMMA LTD', 'new@gamma.com'],
            ['Delta Ltd', 'd@delta.com'],
        ]);

        $this->assertNull($this->findRecord($dvlaOnly), 'A record with only DVLA emails, not re-imported, is removed');
        $this->assertEquals(0, $this->connection->fetchOne(
            'SELECT COUNT(*) FROM notification_interception_company_name WHERE notification_interception_id = ?',
            [$dvlaOnly],
        ), "...along with its company name");

        $beta = $this->findRecord($csrgtToo);
        $this->assertNotNull($beta, 'A record with other emails survives');
        $this->assertNull($beta->getDvlaEmails());
        $this->assertSame('csrgt@beta.com', $beta->getCsrgtEmails());

        $this->assertSame('new@gamma.com', $this->findRecord($reimported)?->getDvlaEmails(), 'Names match case-insensitively');

        $this->assertEqualsCanonicalizing(
            ['Beta Ltd', 'Delta Ltd', 'Gamma Ltd'],
            $this->connection->fetchFirstColumn('SELECT name FROM notification_interception_company_name'),
        );
    }

    private function createRecord(string $name, ?string $dvla = null, ?string $csrgt = null): string
    {
        $record = (new NotificationInterception())
            ->addName($name)
            ->setDvlaEmails($dvla)
            ->setCsrgtEmails($csrgt);
        $this->entityManager->persist($record);

        return (string) $record->getId();
    }

    private function findRecord(string $id): ?NotificationInterception
    {
        $this->entityManager->clear();
        return $this->entityManager->find(NotificationInterception::class, $id);
    }

    /** @param list<array{0: string, 1: string}> $importData Rows as parsed from the upload */
    private function runImport(array $importData): void
    {
        $controller = new class(
            new RequestStack(),
            $this->entityManager,
            'test',
            new CsvEncoder(),
            new LockFactory(new InMemoryStore()),
        ) extends NotificationInterceptionDvlaImportController {
            /** The same steps, in the same order, as import() runs after parsing the upload */
            public function runImportSteps(array $importData): void
            {
                $this->lock = $this->lockFactory->createLock('test-dvla-import');
                $this->lock->acquire();
                $this->dvlaImportData = $importData;

                $this->clearExistingDvlaRecords();
                $this->batchImportDvlaDataFromTempFile();
                $this->clearEmptyRecords();
                $this->lock->release();
            }
        };

        $controller->runImportSteps($importData);
    }
}
