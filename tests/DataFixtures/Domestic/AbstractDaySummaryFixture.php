<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoType;
use App\Entity\Distance;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\HazardousGoods;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

abstract class AbstractDaySummaryFixture  extends Fixture implements DependentFixtureInterface
{
    protected function addDaySummary(ObjectManager $manager, int $dayNumber)
    {
        /** @var SurveyResponse $response */
        $response = $this->getReference('response:simple');

        $day = (new Day())
            ->setNumber($dayNumber)
            ->setResponse($response)
            ->setHasMoreThanFiveStops(true);

        $daySummary = (new DaySummary())
            ->setDay($day)
            ->setOriginLocation('Bognor Regis')
            ->setGoodsLoaded(true)
            ->setGoodsTransferredFrom(Day::TRANSFERRED_NONE)
            ->setDestinationLocation('Chichester')
            ->setGoodsUnloaded(true)
            ->setGoodsTransferredTo(Day::TRANSFERRED_NONE)
            ->setFurthestStop('Barnham')
            ->setBorderCrossingLocation(null)
            ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER)
            ->setGoodsDescriptionOther('Bananas')
            ->setCargoTypeCode(CargoType::CODE_RC_ROLL_CAGES)
            ->setWeightOfGoodsLoaded(2000)
            ->setWeightOfGoodsUnloaded(1800)
            ->setDistanceTravelledLoaded((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(20))
            ->setDistanceTravelledUnloaded((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(15))
            ->setHazardousGoodsCode(HazardousGoods::CODE_0_NOT_HAZARDOUS)
            ->setNumberOfStopsLoading(1)
            ->setNumberOfStopsUnloading(4)
            ->setNumberOfStopsLoadingAndUnloading(0);

        $manager->persist($day);
        $manager->persist($daySummary);

        $this->setReference('day:simple:summary', $day);
        $this->setReference("day-summary:simple:{$dayNumber}", $daySummary);
    }
}