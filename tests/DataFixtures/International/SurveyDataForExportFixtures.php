<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Entity\SurveyResponse as AbstractSurveyResponse;
use App\Entity\SurveyStateInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SurveyDataForExportFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:int:simple', Survey::class);
        $survey->setState(SurveyStateInterface::STATE_CLOSED);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [ResponseStillActiveFixtures::class];
    }
}
