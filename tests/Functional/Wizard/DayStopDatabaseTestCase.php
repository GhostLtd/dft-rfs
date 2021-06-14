<?php

namespace App\Tests\Functional\Wizard;

use App\Entity\AbstractGoodsDescription;
use App\Entity\Domestic\DayStop;
use App\Entity\HazardousGoods;
use App\Repository\Domestic\DayStopRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DayStopDatabaseTestCase implements DatabaseTestCase
{
    protected bool $goodsLoaded;
    protected bool $goodsUnloaded;
    protected bool $isEmpty;
    protected ?bool $isHazardous;
    protected ?bool $atCapacityBySpace;
    protected ?bool $atCapacityByWeight;

    public function __construct(bool $goodsLoaded, bool $goodsUnloaded, bool $isEmpty, ?bool $isHazardous=null, ?bool $atCapacityBySpace=null, ?bool $atCapacityByWeight=null)
    {
        $this->goodsLoaded = $goodsLoaded;
        $this->goodsUnloaded = $goodsUnloaded;
        $this->isEmpty = $isEmpty;
        $this->isHazardous = $isHazardous;
        $this->atCapacityBySpace = $atCapacityBySpace;
        $this->atCapacityByWeight = $atCapacityByWeight;
    }

    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $test): void
    {
        /** @var DayStopRepository $repo */
        $repo = $entityManager->getRepository(DayStop::class);

        /** @var DayStop[] $dayStops */
        $dayStops = $repo->findAll();

        $test::assertCount(1, $dayStops, 'Expected a single dayStop to be in the database');

        $dayStop = $dayStops[0];
        $test::assertEquals($this->goodsLoaded, $dayStop->getGoodsLoaded());
        $test::assertEquals($this->goodsUnloaded, $dayStop->getGoodsUnloaded());
        $test->assertEquals($this->isEmpty ?
            AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY :
            AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER,
            $dayStop->getGoodsDescription());

        if ($this->isHazardous !== null) {
            $test::assertEquals($this->isHazardous ?
                HazardousGoods::CODE_7_RADIOACTIVE_MATERIAL :
                HazardousGoods::CODE_0_NOT_HAZARDOUS,
                $dayStop->getHazardousGoodsCode());
        }

        if ($this->atCapacityBySpace !== null) {
            $test::assertEquals($this->atCapacityBySpace, $dayStop->getWasLimitedBySpace());
        }

        if ($this->atCapacityByWeight !== null) {
            $test::assertEquals($this->atCapacityByWeight, $dayStop->getWasLimitedByWeight());
        }
    }
}