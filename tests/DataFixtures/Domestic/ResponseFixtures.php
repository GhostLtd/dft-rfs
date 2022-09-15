<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Tests\DataFixtures\Domestic\AbstractResponseFixtures;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use Doctrine\Persistence\ObjectManager;

class ResponseFixtures extends AbstractResponseFixtures
{
    public function load(ObjectManager $manager)
    {
        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_YES)
            ->setContactBusinessName('Example Firm')
            ->setContactEmail('test@example.com')
            ->setContactName('Tester');

        $this->loadSurveyAndVehicle($manager, $response);
    }

    public function getDependencies()
    {
        return [SurveyFixtures::class];
    }
}