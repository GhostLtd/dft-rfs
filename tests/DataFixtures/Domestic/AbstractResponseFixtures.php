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
    public function loadSurveyAndVehicle(ObjectManager $manager, SurveyResponse $response, string $surveyState = Survey::STATE_IN_PROGRESS): void
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple', Survey::class);

        $survey
            ->setResponse($response)
            ->setState($surveyState);

        $manager->persist($response);
        $manager->flush();

        // See initialDetailsController line 57
        $vehicle = (new Vehicle())
            ->setRegistrationMark($survey->getRegistrationMark())
            ->setResponse($response);

        $manager->persist($vehicle);
        $manager->flush();

        $this->addReference('response:simple', $response);
        $this->addReference('vehicle:simple', $vehicle);
    }
}
