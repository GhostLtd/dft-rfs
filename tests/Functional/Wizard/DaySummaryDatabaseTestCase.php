<?php

namespace App\Tests\Functional\Wizard;

use App\Entity\Domestic\DaySummary;
use App\Repository\Domestic\DaySummaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DaySummaryDatabaseTestCase implements DatabaseTestCase
{
    protected ?bool $goodsLoaded;
    protected ?string $originLocation;
    protected ?bool $goodsUnloaded;
    protected ?string $destinationLocation;
    protected ?string $hazardousGoodsCode;
    protected ?string $furthestStop;
    protected ?string $borderCrossing;
    protected ?string $distanceLoadedValue;
    protected ?string $distanceLoadedUnit;
    protected ?string $distanceUnloadedValue;
    protected ?string $distanceUnloadedUnit;
    protected ?string $goodsDescription;
    protected ?string $goodsDescriptionOther;
    protected ?string $cargoTypeCode;
    protected ?string $weightOfGoodsLoaded;
    protected ?string $weightOfGoodsUnloaded;
    protected ?string $numberOfStopsLoading;
    protected ?string $numberOfStopsUnloading;
    protected ?string $numberOfStopsLoadingAndUnloading;

    public function __construct(?bool $goodsLoaded,
                                ?string $originLocation,
                                ?bool $goodsUnloaded,
                                ?string $destinationLocation,
                                ?string $hazardousGoodsCode,
                                ?string $furthestStop,
                                ?string $borderCrossing,
                                ?string $distanceLoadedValue,
                                ?string $distanceLoadedUnit,
                                ?string $distanceUnloadedValue,
                                ?string $distanceUnloadedUnit,
                                ?string $goodsDescription,
                                ?string $goodsDescriptionOther,
                                ?string $cargoTypeCode,
                                ?string $weightOfGoodsLoaded,
                                ?string $weightOfGoodsUnloaded,
                                ?string $numberOfStopsLoading,
                                ?string $numberOfStopsUnloading,
                                ?string $numberOfStopsLoadingAndUnloading
    )
    {
        $this->goodsLoaded = $goodsLoaded;
        $this->originLocation = $originLocation;
        $this->goodsUnloaded = $goodsUnloaded;
        $this->destinationLocation = $destinationLocation;
        $this->hazardousGoodsCode = $hazardousGoodsCode;
        $this->furthestStop = $furthestStop;
        $this->borderCrossing = $borderCrossing;
        $this->distanceLoadedValue = $distanceLoadedValue;
        $this->distanceLoadedUnit = $distanceLoadedUnit;
        $this->distanceUnloadedValue = $distanceUnloadedValue;
        $this->distanceUnloadedUnit = $distanceUnloadedUnit;
        $this->goodsDescription = $goodsDescription;
        $this->goodsDescriptionOther = $goodsDescriptionOther;
        $this->cargoTypeCode = $cargoTypeCode;
        $this->weightOfGoodsLoaded = $weightOfGoodsLoaded;
        $this->weightOfGoodsUnloaded = $weightOfGoodsUnloaded;
        $this->numberOfStopsLoading = $numberOfStopsLoading;
        $this->numberOfStopsUnloading = $numberOfStopsUnloading;
        $this->numberOfStopsLoadingAndUnloading = $numberOfStopsLoadingAndUnloading;
    }

    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $test): void
    {
        /** @var DaySummaryRepository $repo */
        $repo = $entityManager->getRepository(DaySummary::class);
        $entityManager->clear();
        $daySummaries = $repo->findAll();

        $test::assertCount(1, $daySummaries, 'Expected a single daySummary to be in the database');

        $daySummary = $daySummaries[0];
        $test::assertEquals($this->goodsLoaded, $daySummary->getGoodsLoaded());
        $test::assertEquals($this->originLocation, $daySummary->getOriginLocation());
        $test::assertEquals($this->goodsUnloaded, $daySummary->getGoodsUnloaded());
        $test::assertEquals($this->destinationLocation, $daySummary->getDestinationLocation());
        $test::assertEquals($this->hazardousGoodsCode, $daySummary->getHazardousGoodsCode());
        $test::assertEquals($this->furthestStop, $daySummary->getFurthestStop());
        $test::assertEquals($this->borderCrossing, $daySummary->getBorderCrossingLocation());
        $test::assertEquals($this->distanceLoadedValue, $daySummary->getDistanceTravelledLoaded()->getValue());
        $test::assertEquals($this->distanceLoadedUnit, $daySummary->getDistanceTravelledLoaded()->getUnit());
        $test::assertEquals($this->distanceUnloadedValue, $daySummary->getDistanceTravelledUnloaded()->getValue());
        $test::assertEquals($this->distanceUnloadedUnit, $daySummary->getDistanceTravelledUnloaded()->getUnit());
        $test::assertEquals($this->goodsDescription, $daySummary->getGoodsDescription());
        $test::assertEquals($this->goodsDescriptionOther, $daySummary->getgoodsDescriptionOther());
        $test::assertEquals($this->cargoTypeCode, $daySummary->getCargoTypeCode());
        $test::assertEquals($this->weightOfGoodsLoaded, $daySummary->getWeightOfGoodsLoaded());
        $test::assertEquals($this->weightOfGoodsUnloaded, $daySummary->getWeightOfGoodsUnloaded());
        $test::assertEquals($this->numberOfStopsLoading, $daySummary->getNumberOfStopsLoading());
        $test::assertEquals($this->numberOfStopsUnloading, $daySummary->getNumberOfStopsUnloading());
        $test::assertEquals($this->numberOfStopsLoadingAndUnloading, $daySummary->getNumberOfStopsLoadingAndUnloading());
    }
}