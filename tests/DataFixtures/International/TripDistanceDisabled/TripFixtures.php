<?php

namespace App\Tests\DataFixtures\International\TripDistanceDisabled;

use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Tests\DataFixtures\International\TripFixtures as BaseTripFixtures;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TripFixtures extends BaseTripFixtures implements DependentFixtureInterface
{
    use DistanceDisabledSurveyTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        parent::load($manager);
        /** @var Vehicle $vehicle */
        $vehicle = $this->getReference('vehicle:1', Vehicle::class);
        $this->updateSurveyDates($manager, $survey = $vehicle->getSurveyResponse()->getSurvey());

        /** @var Trip $trip */
        $trip = $this->getReference('trip:1', Trip::class);
        $trip
            ->setOutboundDate(clone ($survey->getSurveyPeriodStart()))
            ->setReturnDate((clone $survey->getSurveyPeriodStart())->modify('+2 days'))
            ;

        $manager->flush();
    }
}
