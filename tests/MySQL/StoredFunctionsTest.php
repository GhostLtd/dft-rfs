<?php

namespace App\Tests\MySQL;

use App\Serializer\Normalizer\International\TripActionsExportNormalizer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV4;
use Symfony\Component\Uid\UuidV6;

/**
 * The SQL functions in sql/functions/ (used by the IRHS export views) against the PHP they mirror.
 * A view returning rows doesn't prove these ran: they're only called on some branches.
 */
class StoredFunctionsTest extends KernelTestCase
{
    use RunsOnMySql;

    private Connection $connection;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();
        $this->connection = static::getContainer()->get('doctrine.dbal.default_connection');
    }

    public static function dataEmptyUuid(): array
    {
        return [
            'return context' => ['1f0c2a4e-7b3d-6e10-9a5b-0242ac120002', 'return'],
            'outbound context' => ['1f0c2a4e-7b3d-6e10-9a5b-0242ac120002', 'outbound'],
            'other seed' => ['00000000-0000-0000-0000-000000000000', 'return'],
            'non-uuid seed' => ['abc', 'x'],
        ];
    }

    #[DataProvider('dataEmptyUuid')]
    public function testEmptyUuidMatchesTheExportNormalizer(string $seed, string $context): void
    {
        $sql = $this->connection->fetchOne('SELECT irhs_empty_uuid(?, ?)', [$seed, $context]);

        $this->assertSame($this->getGuidFromNormalizer($seed, $context), $sql);
    }

    public static function dataUuid6Times(): array
    {
        return [
            'recent' => ['2026-03-11 09:15:42.123456'],
            'view cut-off date' => ['2026-03-11 00:00:00.000000'],
            'leap day' => ['2024-02-29 23:59:59.999999'],
        ];
    }

    #[DataProvider('dataUuid6Times')]
    public function testUuid6ToTimestampMatchesSymfonyUid(string $time): void
    {
        $uuid = new UuidV6(UuidV6::generate(new \DateTimeImmutable($time, new \DateTimeZone('UTC'))));

        $sql = $this->connection->fetchOne('SELECT uuid6_to_timestamp(?)', [$uuid->toRfc4122()]);

        $this->assertSame($uuid->getDateTime()->format('Y-m-d H:i:s.u'), $sql);
        $this->assertSame($time, $sql);
    }

    public static function dataNotUuid6(): array
    {
        return [
            'v4 uuid' => [(new UuidV4())->toRfc4122()],
            'not a uuid' => ['not-a-uuid'],
        ];
    }

    #[DataProvider('dataNotUuid6')]
    public function testUuid6ToTimestampRejectsOtherValues(string $value): void
    {
        $this->assertNull($this->connection->fetchOne('SELECT uuid6_to_timestamp(?)', [$value]));
    }

    private function getGuidFromNormalizer(string $seed, string $context): string
    {
        $normalizer = (new \ReflectionClass(TripActionsExportNormalizer::class))->newInstanceWithoutConstructor();

        return (new \ReflectionMethod($normalizer, 'getGuid'))->invoke($normalizer, $seed, $context);
    }
}
