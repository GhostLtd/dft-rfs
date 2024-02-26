<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\SurveyResponse as BaseSurveyResponse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class BusinessFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var SurveyResponse $response */
        $response = $this->getReference('response:simple', SurveyResponse::class);

        $response
            ->setNumberOfEmployees(BaseSurveyResponse::EMPLOYEES_10_TO_49)
            ->setBusinessNature('Haulage');

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [ResponseFixtures::class];
    }
}
