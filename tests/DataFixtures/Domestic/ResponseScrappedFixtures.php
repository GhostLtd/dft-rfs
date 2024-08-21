<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyStateInterface;
use Doctrine\Persistence\ObjectManager;

class ResponseScrappedFixtures extends AbstractResponseFixtures
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_SCRAPPED_OR_STOLEN)
            ->setUnableToCompleteDate(new \DateTime('now - 2 days'));

        $this->loadSurveyAndVehicle($manager, $response, SurveyStateInterface::STATE_CLOSED);
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [SurveyFixtures::class];
    }
}