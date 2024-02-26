<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\SurveyStateInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ResponseOnHireButInProgress extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple', Survey::class);
        $survey->setState(SurveyStateInterface::STATE_IN_PROGRESS);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [ResponseOnHireFixtures::class];
    }
}
