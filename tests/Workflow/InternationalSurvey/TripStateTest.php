<?php

namespace App\Tests\Workflow\InternationalSurvey;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle as InternationalVehicle;
use App\Entity\Vehicle;
use App\Tests\Workflow\AbstractWorkflowTestCase;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\TripState;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class TripStateTest extends AbstractWorkflowTestCase
{
    protected Survey $survey;
    protected FormWizardStateInterface|TripState $workflowState;

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
            ->setResponse($surveyResponse)
            ->setSurveyPeriodStart(
                (new \DateTime(Survey::DISABLE_TRIP_DISTANCE_FROM_SURVEY_PERIOD_START_DATE . ' midnight'))
                    ->modify('-1 day')
            );
        $this->workflowState = (new TripState())
            ->setState(TripState::STATE_SWAPPED_TRAILER)
            ->setSubject($trip);

        $this->workflow = $this->workflowRegistry->get($this->workflowState);
    }

    /***
     * Tests
     ***/

    public function rigidTestDataProvider(): array
    {
        return [
            [false, false, [TripState::STATE_DISTANCE]],
            [false, true, [TripState::STATE_NEW_VEHICLE_WEIGHTS, TripState::STATE_DISTANCE]],

            [true, false, [TripState::STATE_SUMMARY]],
            [true, true, [TripState::STATE_NEW_VEHICLE_WEIGHTS, TripState::STATE_SUMMARY]],
        ];
    }

    /**
     * @dataProvider rigidTestDataProvider
     */
    public function testRigid(bool $editing, bool $swappedTrailer, array $states): void
    {
        $this->setSubjectPersisted($editing);
        $this->workflowState->getSubject()->getVehicle()->setAxleConfiguration(Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_RIGID]['vehicle.axle.rigid.3.0']);

        $this->workflowState->getSubject()->setIsSwappedTrailer($swappedTrailer);

        $this->assertStateTransitions($states);
    }

    public function articulatedTestDataProvider(): array
    {
        return [
            [false, false, false, [TripState::STATE_CHANGED_BODY_TYPE, TripState::STATE_DISTANCE]],
            [false, false, true, [TripState::STATE_CHANGED_BODY_TYPE, TripState::STATE_NEW_VEHICLE_WEIGHTS, TripState::STATE_DISTANCE]],
            [false, true, false, [TripState::STATE_CHANGED_BODY_TYPE, TripState::STATE_NEW_VEHICLE_WEIGHTS, TripState::STATE_DISTANCE]],
            [false, true, true, [TripState::STATE_CHANGED_BODY_TYPE, TripState::STATE_NEW_VEHICLE_WEIGHTS, TripState::STATE_DISTANCE]],

            [true, false, false, [TripState::STATE_CHANGED_BODY_TYPE, TripState::STATE_SUMMARY]],
            [true, false, true, [TripState::STATE_CHANGED_BODY_TYPE, TripState::STATE_NEW_VEHICLE_WEIGHTS, TripState::STATE_SUMMARY]],
            [true, true, false, [TripState::STATE_CHANGED_BODY_TYPE, TripState::STATE_NEW_VEHICLE_WEIGHTS, TripState::STATE_SUMMARY]],
            [true, true, true, [TripState::STATE_CHANGED_BODY_TYPE, TripState::STATE_NEW_VEHICLE_WEIGHTS, TripState::STATE_SUMMARY]],
        ];
    }

    /**
     * @dataProvider articulatedTestDataProvider
     */
    public function testArticulated(bool $editing, bool $swappedTrailer, bool $changeBodyType, array $states): void
    {
        $this->setSubjectPersisted($editing);
        $this->workflowState->getSubject()->getVehicle()->setAxleConfiguration(Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_ARTICULATED]['vehicle.axle.articulated.2.2']);

        $this->workflowState->getSubject()->setIsSwappedTrailer($swappedTrailer);
        $this->workflowState->getSubject()->setIsChangedBodyType($changeBodyType);

        $this->assertStateTransitions($states);
    }
}