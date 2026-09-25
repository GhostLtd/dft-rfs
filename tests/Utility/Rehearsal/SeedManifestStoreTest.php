<?php

namespace App\Tests\Utility\Rehearsal;

use App\Entity\Domestic\Survey;
use App\Tests\DataFixtures\Rehearsal\BaselineReferenceFixtures;
use App\Utility\Rehearsal\SeedManifest;
use App\Utility\Rehearsal\SeedManifestStore;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SeedManifestStoreTest extends KernelTestCase
{
    public function testRoundTrip(): void
    {
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([BaselineReferenceFixtures::class]);
        $em = $container->get(EntityManagerInterface::class);
        $store = $container->get(SeedManifestStore::class);

        self::assertNull($store->find());

        $manifest = new SeedManifest(
            new \DateTimeImmutable('2026-09-10 09:00:00', new \DateTimeZone('Europe/London')),
            ['from' => '2026-08-15', 'disabled' => [], 'smsCap' => 20000],
            ['csrgt:r1-due' => ['class' => Survey::class, 'id' => 's-1']],
            'u-1',
            ['h-1', 'h-2'],
        );
        $store->write($manifest);
        $em->flush();

        $found = $store->find();
        self::assertEquals($manifest->toArray(), $found->toArray());
        self::assertSame($manifest->seededAt->getTimestamp(), $found->seededAt->getTimestamp(), 'the exact instant survives, whatever the DB/PHP timezones');
        self::assertSame(['h-1', 'h-2'], $found->historyRowIds);
        self::assertSame(['s-1'], $found->surveyIds(Survey::class));
        self::assertFalse($found->isStale(new \DateTimeImmutable('2026-09-11 08:59:00', new \DateTimeZone('Europe/London'))));
        self::assertTrue($found->isStale(new \DateTimeImmutable('2026-09-11 09:01:00', new \DateTimeZone('Europe/London'))));
    }
}
