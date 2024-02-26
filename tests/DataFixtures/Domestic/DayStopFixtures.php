<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoType;
use App\Entity\Distance;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\HazardousGoods;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class DayStopFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var SurveyResponse $response */
        $response = $this->getReference('response:simple', SurveyResponse::class);

        $day = (new Day())
            ->setNumber(1)
            ->setResponse($response)
            ->setHasMoreThanFiveStops(false);

        $dayStop1 = (new DayStop())
            ->setNumber(1)
            ->setDay($day)
            ->setBorderCrossingLocation(null)
            ->setCargoTypeCode(CargoType::CODE_RC_ROLL_CAGES)
            ->setOriginLocation('Bognor Regis')
            ->setGoodsLoaded(true)
            ->setGoodsTransferredFrom(Day::TRANSFERRED_NONE)
            ->setDestinationLocation('Chichester')
            ->setGoodsUnloaded(true)
            ->setGoodsTransferredTo(Day::TRANSFERRED_NONE)
            ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER)
            ->setGoodsDescriptionOther('Bananas')
            ->setWeightOfGoodsCarried(800)
            ->setDistanceTravelled((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(20))
            ->setHazardousGoodsCode(HazardousGoods::CODE_0_NOT_HAZARDOUS)
            ->setWasAtCapacity(false)
            ->setWasLimitedBySpace(false)
            ->setWasLimitedByWeight(false);

        $dayStop2 = (new DayStop())
            ->setNumber(2)
            ->setDay($day)
            ->setBorderCrossingLocation(null)
            ->setCargoTypeCode(CargoType::CODE_RC_ROLL_CAGES)
            ->setOriginLocation('Worthing')
            ->setGoodsLoaded(true)
            ->setGoodsTransferredFrom(Day::TRANSFERRED_NONE)
            ->setDestinationLocation('Bognor Regis')
            ->setGoodsUnloaded(true)
            ->setGoodsTransferredTo(Day::TRANSFERRED_NONE)
            ->setGoodsDescription(AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER)
            ->setGoodsDescriptionOther('Oranges')
            ->setWeightOfGoodsCarried(300)
            ->setDistanceTravelled((new Distance())->setUnit(Distance::UNIT_MILES)->setValue(30))
            ->setHazardousGoodsCode(HazardousGoods::CODE_0_NOT_HAZARDOUS)
            ->setWasAtCapacity(false)
            ->setWasLimitedBySpace(false)
            ->setWasLimitedByWeight(false);

        $manager->persist($day);
        $manager->persist($dayStop1);
        $manager->persist($dayStop2);

        $this->addReference('day:simple:stop', $day);
        $this->addReference('day-stop:simple:1', $dayStop1);
        $this->addReference('day-stop:simple:2', $dayStop2);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [VehicleFixtures::class];
    }
}
