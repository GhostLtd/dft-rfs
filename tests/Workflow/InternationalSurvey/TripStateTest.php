<?php

namespace App\Tests\Workflow\InternationalSurvey;

use App\Entity\International\Trip;
use App\Entity\International\Vehicle as InternationalVehicle;
use App\Entity\Vehicle;
use App\Workflow\InternationalSurvey\TripState;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class TripStateTest extends WebTestCase
{
    private TripState $tripState;
    private WorkflowInterface $workflow;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();

        $trip = new Trip();
        $vehicle = (new InternationalVehicle())
            ->setBodyType(Vehicle::BODY_TYPE_BOX)
            ->setGrossWeight(2000)
            ->setCarryingCapacity(1000)
        ;
        $trip->setVehicle($vehicle);
        $this->tripState = (new TripState())
            ->setState(TripState::STATE_SWAPPED_TRAILER)
            ->setSubject($trip);

        $container = static::getContainer()->get('test.service_container');
        $workflowRegistry = $container->get('workflow.registry');
        $this->workflow = $workflowRegistry->get($this->tripState);
    }

    protected function setTripPersisted($persisted = true)
    {
        $refl = new \ReflectionClass($this->tripState->getSubject());
        $idProp = $refl->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($this->tripState->getSubject(), $persisted ? 'fake-id' : null);
    }

    protected function assertOneTransitionAndApply()
    {
        $transitions = $this->workflow->getEnabledTransitions($this->tripState);
        $this->assertCount(1, $transitions);
        $this->workflow->apply($this->tripState, $transitions[0]->getName());
    }

    protected function assertTripStateEquals($state)
    {
        $this->assertEquals($state, $this->tripState->getState());
    }

    protected function assertStateTransitions($states)
    {
        foreach ($states as $state)
        {
            $this->assertOneTransitionAndApply();
            $this->assertTripStateEquals($state);
        }
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
        $this->setTripPersisted($editing);
        $this->tripState->getSubject()->getVehicle()->setAxleConfiguration(Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_RIGID]['vehicle.axle.rigid.3.0']);

        $this->tripState->getSubject()->setIsSwappedTrailer($swappedTrailer);

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
        $this->setTripPersisted($editing);
        $this->tripState->getSubject()->getVehicle()->setAxleConfiguration(Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_ARTICULATED]['vehicle.axle.articulated.2.2']);

        $this->tripState->getSubject()->setIsSwappedTrailer($swappedTrailer);
        $this->tripState->getSubject()->setIsChangedBodyType($changeBodyType);

        $this->assertStateTransitions($states);
    }
}