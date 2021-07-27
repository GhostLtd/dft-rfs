<?php

namespace App\Tests\Functional\Wizard;

use App\Entity\Domestic\DayStop;
use App\Repository\Domestic\DayStopRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DayStopDatabaseTestCase implements DatabaseTestCase
{
    protected ?bool $goodsLoaded;
    protected ?string $originLocation;
    protected ?bool $goodsUnloaded;
    protected ?string $destinationLocation;
    protected ?string $borderCrossing;
    protected ?string $distanceTravelledValue;
    protected ?string $distanceTravelledUnit;
    protected ?string $goodsDescription;
    protected ?string $goodsDescriptionOther;
    protected ?string $hazardousGoodsCode;
    protected ?string $cargoTypeCode;
    protected ?string $weightOfGoods;
    protected ?bool $atCapacityBySpace;
    protected ?bool $atCapacityByWeight;

    public function __construct(
        bool $goodsLoaded,
        string $originLocation,
        ?bool $goodsUnloaded,
        ?string $destinationLocation,
        ?string $borderCrossing,
        ?string $distanceTravelledValue,
        ?string $distanceTravelledUnit,
        ?string $goodsDescription,
        ?string $goodsDescriptionOther,
        ?string $hazardousGoodsCode,
        ?string $cargoTypeCode,
        ?string $weightOfGoods,
        ?bool $atCapacityBySpace,
        ?bool $atCapacityByWeight
    ) {
        $this->goodsLoaded = $goodsLoaded;
        $this->originLocation = $originLocation;
        $this->goodsUnloaded = $goodsUnloaded;
        $this->destinationLocation = $destinationLocation;
        $this->borderCrossing = $borderCrossing;
        $this->distanceTravelledValue = $distanceTravelledValue;
        $this->distanceTravelledUnit = $distanceTravelledUnit;
        $this->goodsDescription = $goodsDescription;
        $this->goodsDescriptionOther = $goodsDescriptionOther;
        $this->hazardousGoodsCode = $hazardousGoodsCode;
        $this->cargoTypeCode = $cargoTypeCode;
        $this->weightOfGoods = $weightOfGoods;
        $this->atCapacityBySpace = $atCapacityBySpace;
        $this->atCapacityByWeight = $atCapacityByWeight;
    }

    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $test): void
    {
        /** @var DayStopRepository $repo */
        $repo = $entityManager->getRepository(DayStop::class);
        $entityManager->clear();
        $dayStops = $repo->findAll();

        $test::assertCount(1, $dayStops, 'Expected a single dayStop to be in the database');

        $dayStop = $dayStops[0];
        $test::assertEquals($this->goodsLoaded, $dayStop->getGoodsLoaded());
        $test::assertEquals($this->originLocation, $dayStop->getOriginLocation());
        $test::assertEquals($this->goodsUnloaded, $dayStop->getGoodsUnloaded());
        $test::assertEquals($this->destinationLocation, $dayStop->getDestinationLocation());
        $test::assertEquals($this->borderCrossing, $dayStop->getBorderCrossingLocation());
        $test::assertEquals($this->distanceTravelledValue, $dayStop->getDistanceTravelled()->getValue());
        $test::assertEquals($this->distanceTravelledUnit, $dayStop->getDistanceTravelled()->getUnit());
        $test::assertEquals($this->goodsDescription, $dayStop->getGoodsDescription());
        $test::assertEquals($this->goodsDescriptionOther, $dayStop->getGoodsDescriptionOther());
        $test::assertEquals($this->hazardousGoodsCode, $dayStop->getHazardousGoodsCode());
        $test::assertEquals($this->cargoTypeCode, $dayStop->getCargoTypeCode());
        $test::assertEquals($this->weightOfGoods, $dayStop->getWeightOfGoodsCarried());
        $test::assertEquals($this->atCapacityBySpace, $dayStop->getWasLimitedBySpace());
        $test::assertEquals($this->atCapacityByWeight, $dayStop->getWasLimitedByWeight());
    }
}