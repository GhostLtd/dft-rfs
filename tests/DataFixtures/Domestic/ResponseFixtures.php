<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\SurveyResponse;
use Doctrine\Persistence\ObjectManager;

class ResponseFixtures extends AbstractResponseFixtures
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        $response = (new SurveyResponse())
            ->setIsInPossessionOfVehicle(SurveyResponse::IN_POSSESSION_YES)
            ->setContactBusinessName('Example Firm')
            ->setContactEmail('test@example.com')
            ->setContactName('Tester')
            ->setIsExemptVehicleType(false);

        $this->loadSurveyAndVehicle($manager, $response);
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [SurveyFixtures::class];
    }
}