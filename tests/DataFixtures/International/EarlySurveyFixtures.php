<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\International\Survey;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EarlySurveyFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:int:simple', Survey::class);
        $survey->setSurveyPeriodEnd(new \DateTime('next week'));

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            SurveyFixtures::class,
        ];
    }
}
