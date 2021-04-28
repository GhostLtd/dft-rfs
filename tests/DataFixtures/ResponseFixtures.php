<?php

namespace App\Tests\DataFixtures;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ResponseFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(true)
            ->setNumberOfEmployees(10)
            ->setContactEmail('test@example.com')
            ->setContactName('Tester')
            ->setContactTelephone(null)
            ->setBusinessNature('Haulage');

        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple');

        $survey->setResponse($response);

        $manager->persist($response);
        $this->setReference('response:simple', $response);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [SurveyFixtures::class];
    }
}