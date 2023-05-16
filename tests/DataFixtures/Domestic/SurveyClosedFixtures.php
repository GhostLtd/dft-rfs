<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\SurveyStateInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SurveyClosedFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple');

        $survey->setState(SurveyStateInterface::STATE_CLOSED);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [SurveyFixtures::class];
    }
}