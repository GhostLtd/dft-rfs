<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EarlySurveyFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple', Survey::class);
        $survey->setSurveyPeriodEnd(new \DateTime('next week'));

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SurveyFixtures::class,
        ];
    }
}