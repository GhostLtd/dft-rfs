<?php

namespace App\Tests\DataFixtures;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ResponseFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_YES)
            ->setContactEmail('test@example.com')
            ->setContactName('Tester');

        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple');

        $survey
            ->setResponse($response)
            ->setState(Survey::STATE_IN_PROGRESS);

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

    public function getDependencies()
    {
        return [SurveyFixtures::class];
    }
}