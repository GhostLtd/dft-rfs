<?php

namespace App\Tests\DataFixtures;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use Doctrine\Persistence\ObjectManager;

class ResponseSoldFixtures extends AbstractResponseFixtures
{
    public function load(ObjectManager $manager)
    {
        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_SOLD)
            ->setNewOwnerEmail('test@example.com')
            ->setNewOwnerName('Tester')
            ->setNewOwnerTelephone('12345')
            ->setUnableToCompleteDate(new \DateTime('now - 2 days'));

        $this->loadSurveyAndVehicle($manager, $response, Survey::STATE_CLOSED);
    }

    public function getDependencies()
    {
        return [SurveyFixtures::class];
    }
}