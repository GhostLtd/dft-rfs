<?php

namespace App\Tests\DataFixtures\Domestic;

use App\Entity\Domestic\Survey;
use Doctrine\Persistence\ObjectManager;

class ResponseExemptVehicleTypeFixtures extends AbstractResponseFixtures
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:simple', Survey::class);
        $survey
            ->getResponse()
            ->setIsExemptVehicleType(true);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [ResponseFixtures::class];
    }
}
