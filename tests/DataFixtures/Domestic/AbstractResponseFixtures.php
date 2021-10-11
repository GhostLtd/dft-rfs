<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

abstract class AbstractResponseFixtures extends Fixture implements DependentFixtureInterface
{
    public function loadSurveyAndVehicle(ObjectManager $manager, SurveyResponse $response, string $surveyState = Survey::STATE_IN_PROGRESS)
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple');

        $survey
            ->setResponse($response)
            ->setState($surveyState);

        // See initialDetailsController line 57
        $vehicle = (new Vehicle())
            ->setRegistrationMark($survey->getRegistrationMark())
            ->setResponse($response);

        $manager->persist($response);
        $manager->persist($vehicle);

        $this->setReference('response:simple', $response);
        $this->setReference('vehicle:simple', $vehicle);

        $manager->flush();
    }
}