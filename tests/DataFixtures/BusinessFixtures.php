<?php

namespace App\Tests\DataFixtures;

use App\Entity\Domestic\SurveyResponse;
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
            ->setNumberOfEmployees(10)
            ->setBusinessNature('Haulage');

        $manager->persist($response);
        $manager->flush();
    }

    public function getDependencies()
    {
        return [ResponseFixtures::class];
    }
}