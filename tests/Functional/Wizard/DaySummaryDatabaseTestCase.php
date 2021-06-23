<?php

namespace App\Tests\Functional\Wizard;

use App\Entity\Domestic\DaySummary;
use App\Entity\HazardousGoods;
use App\Repository\Domestic\DayStopRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DaySummaryDatabaseTestCase implements DatabaseTestCase
{
    protected bool $goodsLoaded;
    protected ?bool $goodsUnloaded;
    protected ?bool $isHazardous;

    public function __construct(bool $goodsLoaded, ?bool $goodsUnloaded=null, ?bool $isHazardous=null)
    {
        $this->goodsLoaded = $goodsLoaded;
        $this->goodsUnloaded = $goodsUnloaded;
        $this->isHazardous = $isHazardous;
    }

    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $test): void
    {
        /** @var DayStopRepository $repo */
        $repo = $entityManager->getRepository(DaySummary::class);

        /** @var DaySummary[] $daySummary */
        $daySummary = $repo->findAll();

        $test::assertCount(1, $daySummary, 'Expected a single daySummary to be in the database');

        $dayStop = $daySummary[0];
        $test::assertEquals($this->goodsLoaded, $dayStop->getGoodsLoaded());

        if ($this->goodsUnloaded !== null) {
            $test::assertEquals($this->goodsUnloaded, $dayStop->getGoodsUnloaded());
        }

        if ($this->isHazardous !== null) {
            $test::assertEquals($this->isHazardous ?
                HazardousGoods::CODE_7_RADIOACTIVE_MATERIAL :
                HazardousGoods::CODE_0_NOT_HAZARDOUS,
                $dayStop->getHazardousGoodsCode());
        }
    }
}