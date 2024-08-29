<?php

namespace App\Tests\Workflow\InternationalSurvey;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle as InternationalVehicle;
use App\Entity\Vehicle;
use App\Tests\Workflow\AbstractWorkflowTestCase;
use App\Workflow\InternationalSurvey\TripState;

class RemoveTripDistanceTest extends AbstractWorkflowTestCase
{
    protected Survey $survey;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $vehicle = (new InternationalVehicle())
            ->setBodyType(Vehicle::BODY_TYPE_BOX)
            ->setGrossWeight(2000)
            ->setCarryingCapacity(1000)
            ->addTrip($trip = new Trip())
        ;
        $surveyResponse = (new SurveyResponse())
            ->addVehicle($vehicle);
        $this->survey = (new Survey())
            ->setResponse($surveyResponse);
        $this->workflowState = (new TripState())
            ->setState(TripState::STATE_SWAPPED_TRAILER)
            ->setSubject($trip);

        $this->workflow = $this->workflowRegistry->get($this->workflowState);
    }

    /***
     * Tests
     ***/

    public function tripDistanceDataProvider(): array
    {
        return [
            ['-1 sec', true, TripState::STATE_DISTANCE, [TripState::STATE_SUMMARY]],
            [null, true, TripState::STATE_DISTANCE, []],

            ['-1 sec', false, TripState::STATE_CHANGED_BODY_TYPE, [TripState::STATE_DISTANCE]],
            ['-1 sec', false, TripState::STATE_NEW_VEHICLE_WEIGHTS, [TripState::STATE_DISTANCE]],
            [null, false, TripState::STATE_CHANGED_BODY_TYPE, [TripState::STATE_COUNTRIES_TRANSITTED]],
            [null, false, TripState::STATE_NEW_VEHICLE_WEIGHTS, [TripState::STATE_COUNTRIES_TRANSITTED]],
        ];
    }

    /**
     * @dataProvider tripDistanceDataProvider
     */
    public function testTripDistance(?string $surveyStartDateModifier, bool $editing, string $initialState, array $states): void
    {
        if ($editing && empty($states)) {
            $this->assertFalse($this->workflowState->isValidAlternativeStartState($initialState));
        }
        $surveyStart = new \DateTime(Survey::DISABLE_TRIP_DISTANCE_FROM_SURVEY_PERIOD_START_DATE . ' midnight');
        if ($surveyStartDateModifier) {
            $surveyStart->modify($surveyStartDateModifier);
        }
        $this->survey->setSurveyPeriodStart($surveyStart);
        $this->setSubjectPersisted($editing);
        $this->workflowState->setState($initialState);

        $this->assertStateTransitions($states);
    }
}