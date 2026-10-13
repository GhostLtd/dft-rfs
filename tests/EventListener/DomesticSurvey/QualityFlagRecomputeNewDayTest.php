<?php

namespace App\Tests\EventListener\DomesticSurvey;

use App\Entity\AbstractGoodsDescription;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Embeddable\Distance;
use App\Tests\DataFixtures\Domestic\ResponseFixtures;
use App\Utility\QualityCheck\QualityCheckEngine;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Regression: a Day created in the same flush as the recompute must be included in the cached
 * quality flag count. DataConsistencyListener initialises $response->days during onFlush (before the
 * Day is inserted), so the new Day is only seen if the inverse side was kept in sync when it was created.
 */
class QualityFlagRecomputeNewDayTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private string $responseId;

    #[\Override]
    protected function setUp(): void
    {
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $refs = $container->get(DatabaseToolCollection::class)->get()
            ->loadFixtures([ResponseFixtures::class])
            ->getReferenceRepository();
        $this->responseId = $refs->getReference('response:simple', SurveyResponse::class)->getId();
        $this->entityManager->clear();
    }

    public function testNewDayWithStopsIsIncludedInRecompute(): void
    {
        $response = $this->loadResponse();

        // Same shape as DomesticSurvey\DayStopController::getDayStop() creating a brand-new day
        $day = (new Day())
            ->setResponse($response)
            ->setHasMoreThanFiveStops(false)
            ->setNumber(1);
        $this->entityManager->persist($day);

        $stop = (new DayStop())
            ->setOriginLocation('Bognor Regis')
            ->setGoodsLoaded(false)
            ->setGoodsTransferredFrom(Day::TRANSFERRED_NONE)
            ->setDestinationLocation('Chichester')
            ->setGoodsUnloaded(false)
            ->setGoodsTransferredTo(Day::TRANSFERRED_NONE)
            ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY)
            ->setWeightOfGoodsCarried(null)
            ->setDistanceTravelled((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(5));
        $day->addStop($stop);
        $this->entityManager->persist($stop);

        $this->entityManager->flush();

        $this->assertCachedCountMatchesActual(1);
    }

    public function testNewDayWithSummaryIsIncludedInRecompute(): void
    {
        $response = $this->loadResponse();

        // Same shape as DomesticSurvey\DaySummaryController creating a brand-new day
        $day = (new Day())
            ->setResponse($response)
            ->setHasMoreThanFiveStops(true)
            ->setNumber(1);
        $this->entityManager->persist($day);

        $summary = (new DaySummary())
            ->setOriginLocation('Bognor Regis')
            ->setGoodsLoaded(false)
            ->setGoodsTransferredFrom(Day::TRANSFERRED_NONE)
            ->setDestinationLocation('Chichester')
            ->setGoodsUnloaded(false)
            ->setGoodsTransferredTo(Day::TRANSFERRED_NONE)
            ->setFurthestStop('Barnham')
            ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY)
            ->setWeightOfGoodsLoaded(0)
            ->setWeightOfGoodsUnloaded(0)
            ->setDistanceTravelledLoaded((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(0))
            ->setDistanceTravelledUnloaded((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(15))
            ->setNumberOfStopsLoading(0)
            ->setNumberOfStopsUnloading(0)
            ->setNumberOfStopsLoadingAndUnloading(0);
        $day->setSummary($summary);
        $this->entityManager->persist($summary);

        $this->entityManager->flush();

        $this->assertCachedCountMatchesActual(1);
    }

    private function loadResponse(): SurveyResponse
    {
        $response = $this->entityManager->find(SurveyResponse::class, $this->responseId);
        self::assertNotNull($response);
        return $response;
    }

    private function assertCachedCountMatchesActual(int $expected): void
    {
        $this->entityManager->clear();
        $response = $this->loadResponse();
        $actual = static::getContainer()->get(QualityCheckEngine::class)->run($response);

        self::assertSame($expected, $actual->count(), 'Sanity check: the new day should raise a flag');
        self::assertSame($expected, $response->getQualityFlagCount());
        self::assertSame($actual->signature(), $response->getQualityFlagSignature());
    }
}
