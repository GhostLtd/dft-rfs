<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\SurveyInterface;
use Doctrine\Persistence\ObjectManager;

class ResponseScrappedButInProgressFixtures extends AbstractResponseFixtures
{
    public function load(ObjectManager $manager)
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple');
        $survey->setState(SurveyInterface::STATE_IN_PROGRESS);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ResponseScrappedFixtures::class];
    }
}