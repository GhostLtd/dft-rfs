<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyResponse as BaseSurveyResponse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BusinessFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var SurveyResponse $response */
        $response = $this->getReference('response:simple');

        $response
            ->setNumberOfEmployees(BaseSurveyResponse::EMPLOYEES_10_TO_49)
            ->setBusinessNature('Haulage');

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ResponseFixtures::class];
    }
}