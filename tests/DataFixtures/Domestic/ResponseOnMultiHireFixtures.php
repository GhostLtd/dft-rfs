<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Tests\DataFixtures\Domestic\AbstractResponseFixtures;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use App\Tests\DataFixtures\Domestic\SurveyHiredFixtures;
use Doctrine\Persistence\ObjectManager;

class ResponseOnMultiHireFixtures extends AbstractResponseFixtures
{
    public function load(ObjectManager $manager)
    {
        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_ON_HIRE)
            ->setHireeEmail('test@example.com')
            ->setHireeEmail('Tester')
            ->setHireeTelephone('12345')
            ->setUnableToCompleteDate(new \DateTime('now - 2 days'));

        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple');

        /** @var Survey $hiredSurvey */
        $hiredSurvey = $this->getReference('survey:hired');

        $hiredSurvey->setReissuedSurvey($survey);

        $this->loadSurveyAndVehicle($manager, $response, Survey::STATE_CLOSED);
    }

    public function getDependencies()
    {
        return [SurveyFixtures::class, SurveyHiredFixtures::class];
    }
}