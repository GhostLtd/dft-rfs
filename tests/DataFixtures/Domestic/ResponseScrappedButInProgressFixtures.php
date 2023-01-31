<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Tests\DataFixtures\Domestic\AbstractResponseFixtures;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use Doctrine\Persistence\ObjectManager;

class ResponseScrappedButInProgressFixtures extends AbstractResponseFixtures
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
        return [ResponseScrappedFixtures::class];
    }
}