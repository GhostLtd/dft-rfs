<?php

namespace App\Tests\NewFunctional\Wizard\International;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTest;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

abstract class AbstractSurveyTest extends AbstractPasscodeWizardTest
{
    protected function getTrip(EntityManagerInterface $entityManager, TestCase $test): Trip {
        $survey = $this->getSurvey($entityManager, $test);

        $response = $survey->getResponse();
        $this->assertInstanceOf(SurveyResponse::class, $response, 'Expect response to be filled');

        $vehicles = $response->getVehicles();
        $this->assertCount(1, $vehicles, 'Expect only one vehicle in the database');

        $trips = $vehicles[0]->getTrips();
        $this->assertCount(1, $trips, 'Expect only one trip in the database');

        return $trips[0];
    }

    protected function getSurvey(EntityManagerInterface $entityManager, TestCase $test): Survey
    {
        $repository = $entityManager->getRepository(Survey::class);

        $entityManager->clear();
        $surveys = $repository->findAll();

        $test->assertCount(1, $surveys, 'Expected a single survey to be in the database');

        $survey = $surveys[0];
        $test->assertInstanceOf(Survey::class, $survey);

        return $survey;
    }
}
