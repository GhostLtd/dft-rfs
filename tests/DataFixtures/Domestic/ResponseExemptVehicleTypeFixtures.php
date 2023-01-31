<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use Doctrine\Persistence\ObjectManager;

class ResponseExemptVehicleTypeFixtures extends AbstractResponseFixtures
{
    public function load(ObjectManager $manager)
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple');
        $survey
            ->getResponse()
            ->setIsExemptVehicleType(true);

        $manager->flush();
    }

    public function getDependencies()
    {
        return [ResponseFixtures::class];
    }
}