<?php

namespace App\Tests\DataFixtures;

use App\Entity\Domestic\SurveyResponse;
use Doctrine\Persistence\ObjectManager;

class ResponseFixtures extends AbstractResponseFixtures
{
    public function load(ObjectManager $manager)
    {
        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_YES)
            ->setContactEmail('test@example.com')
            ->setContactName('Tester');

        $this->loadSurveyAndVehicle($manager, $response);
    }

    public function getDependencies()
    {
        return [SurveyFixtures::class];
    }
}