<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\International\SurveyResponse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ResponseDomesticOnlyFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var SurveyResponse $response */
        $response = $this->getReference('response:int', SurveyResponse::class);
        $response->setActivityStatus(SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [ResponseStillActiveFixtures::class];
    }
}
