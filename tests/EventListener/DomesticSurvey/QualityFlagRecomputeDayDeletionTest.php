<?php

namespace App\Tests\EventListener\DomesticSurvey;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Embeddable\Distance;
use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Utility\QualityCheck\QualityCheckEngine;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Regression: when DataConsistencyListener deletes a response's days (exempt vehicle, or not in possession), the
 * cached quality flag count must be recomputed. The days are detached via SurveyResponse::removeDay() before being
 * removed, so the recompute listener has to resolve their response from the original entity data.
 */
class QualityFlagRecomputeDayDeletionTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private string $responseId;

    #[\Override]
    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $refs = $container->get(DatabaseToolCollection::class)->get()
            ->loadFixtures([DayStopFixtures::class])
            ->getReferenceRepository();
        $this->responseId = $refs->getReference('response:simple', SurveyResponse::class)->getId();
        $this->entityManager->clear();

        // Zero-distance stops give the response a real, non-zero cached count, which is then marked seen
        $response = $this->loadResponse();
        foreach ($response->getDays() as $day) {
            foreach ($day->getStops() as $stop) {
                $stop->setDistanceTravelled((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(0));
            }
        }
        $this->entityManager->flush();
        $response->setQualityFlagsSeen(true);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $response = $this->loadResponse();
        self::assertGreaterThan(0, $response->getQualityFlagCount(), 'Sanity check: setup should raise a flag');
        self::assertTrue($response->getQualityFlagsSeen());
        $this->entityManager->clear();
    }

    public static function dayDeletingChangeProvider(): array
    {
        return [
            'exempt vehicle' => [fn(SurveyResponse $r) => $r->setIsExemptVehicleType(true)],
            'sold' => [fn(SurveyResponse $r) => $r->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_SOLD)],
            'on hire' => [fn(SurveyResponse $r) => $r->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_ON_HIRE)],
            'scrapped or stolen' => [fn(SurveyResponse $r) => $r->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN)],
        ];
    }

    /** @param \Closure(SurveyResponse): mixed $change */
    #[DataProvider('dayDeletingChangeProvider')]
    public function testDeletedDaysAreRecomputed(\Closure $change): void
    {
        $change($this->loadResponse());
        $this->entityManager->flush();
        $this->entityManager->clear();

        $response = $this->loadResponse();
        $actual = static::getContainer()->get(QualityCheckEngine::class)->run($response);

        self::assertCount(0, $response->getDays(), 'Sanity check: DataConsistencyListener should have deleted the days');
        self::assertSame(0, $actual->count());
        self::assertSame(0, $response->getQualityFlagCount());
        self::assertSame($actual->signature(), $response->getQualityFlagSignature());
        self::assertFalse($response->getQualityFlagsSeen());
    }

    private function loadResponse(): SurveyResponse
    {
        $response = $this->entityManager->find(SurveyResponse::class, $this->responseId);
        self::assertNotNull($response);
        return $response;
    }
}
