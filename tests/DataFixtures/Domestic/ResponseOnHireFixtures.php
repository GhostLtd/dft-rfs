<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyStateInterface;
use Doctrine\Persistence\ObjectManager;

class ResponseOnHireFixtures extends AbstractResponseFixtures
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_ON_HIRE)
            ->setHireeEmail('test@example.com')
            ->setHireeName('Tester')
            ->setHireeTelephone('12345')
            ->setUnableToCompleteDate(new \DateTime('now - 2 days'));

        $survey = $this->loadSurveyAndVehicle($manager, $response, SurveyStateInterface::STATE_CLOSED);
        $survey->setSurveyPeriodStart(new \DateTime('next monday'));
        $survey->setSurveyPeriodEnd(new \DateTime('1 week next monday'));
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [SurveyFixtures::class];
    }
}