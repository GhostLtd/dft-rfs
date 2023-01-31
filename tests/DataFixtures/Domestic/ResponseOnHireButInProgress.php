<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ResponseOnHireButInProgress extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple');
        $survey->setState(Survey::STATE_IN_PROGRESS);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [ResponseOnHireFixtures::class];
    }
}