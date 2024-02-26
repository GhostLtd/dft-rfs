<?php

namespace App\Tests\DataFixtures\International;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Entity\SurveyResponse as AbstractSurveyResponse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ResponseStillActiveFixtures extends Fixture implements DependentFixtureInterface
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Survey $survey */
        $survey = $this->getReference('survey:int:simple', Survey::class);

        $response = (new SurveyResponse())
            ->setContactName('Tom')
            ->setContactEmail('tom@example.com')
            ->setContactTelephone('0800 8118181')
            ->setNumberOfEmployees(AbstractSurveyResponse::EMPLOYEES_10_TO_49)
            ->setActivityStatus(SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE)
            ->setBusinessNature('Bread haulage')
            ->setAnnualInternationalJourneyCount('150')
            ->setInitialDetailsSignedOff(true);

        $survey
            ->setResponse($response)
            ->setResponseStartDate(new \DateTime());

        $this->setReference('response:int', $response);

        $manager->persist($response);

        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [SurveyFixtures::class];
    }
}
