<?php

namespace App\Tests\Functional\Wizard;

use App\Entity\Domestic\SurveyResponse;
use App\Repository\Domestic\SurveyResponseRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class FinalQuestionsDatabaseTestCase implements DatabaseTestCase
{
    protected ?string $state;
    protected ?string $reasonForEmptySurvey;
    protected ?string $vehicleFuelValue;
    protected ?string $vehicleFuelUnit;

    public function __construct(?string $state, ?string $reasonForEmptySurvey, ?string $vehicleFuelValue, ?string $vehicleFuelUnit)
    {
        $this->state = $state;
        $this->reasonForEmptySurvey = $reasonForEmptySurvey;
        $this->vehicleFuelValue = $vehicleFuelValue;
        $this->vehicleFuelUnit = $vehicleFuelUnit;
    }

    public function checkDatabaseAsExpected(EntityManagerInterface $entityManager, TestCase $test): void
    {
        /** @var SurveyResponseRepository $repo */
        $repo = $entityManager->getRepository(SurveyResponse::class);
        $entityManager->clear();
        $responses = $repo->findAll();

        $test::assertCount(1, $responses, 'Expected a single surveyResponse to be in the database');

        $response = $responses[0];
        $survey = $response->getSurvey();
        $vehicle = $response->getVehicle();
        $fuelQuantity = $vehicle->getFuelQuantity();

        $test::assertEquals($this->state, $survey->getState());
        $test::assertEquals($this->reasonForEmptySurvey, $response->getReasonForEmptySurvey());
        $test::assertEquals($this->vehicleFuelValue, $fuelQuantity->getValue());
        $test::assertEquals($this->vehicleFuelUnit, $fuelQuantity->getUnit());
    }
}