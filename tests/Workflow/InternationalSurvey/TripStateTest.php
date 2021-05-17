<?php


namespace App\Tests\Workflow\InternationalSurvey;


use App\Entity\International\Trip;
use App\Entity\International\Vehicle as InternationalVehicle;
use App\Entity\Vehicle;
use App\Workflow\InternationalSurvey\TripState;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Workflow\WorkflowInterface;

class TripStateTest extends WebTestCase
{
    /** @var TripState */
    private $tripState;

    /** @var WorkflowInterface */
    private $workflow;

    protected function setUp()
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

        $container = self::$kernel->getContainer()->get('test.service_container');
        $workflowRegistry = $container->get('workflow.registry');
        $this->workflow = $workflowRegistry->get($this->tripState);

        $token = new AnonymousToken('secret', new User('anon.', ''));
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $container->get('security.token_storage');
        $tokenStorage->setToken($token);
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

    public function rigidTestDataProvider()
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
     * @param $swappedTrailer
     * @param $changeBodyType
     * @param $states
     */
    public function testRigid($editing, $swappedTrailer, $states)
    {
        $this->setTripPersisted($editing);
        $this->tripState->getSubject()->getVehicle()->setAxleConfiguration(Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_RIGID]['vehicle.axle.rigid.3.0']);

        $this->tripState->getSubject()->setIsSwappedTrailer($swappedTrailer);

        $this->assertStateTransitions($states);
    }

    public function articulatedTestDataProvider()
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
     * @param $swappedTrailer
     * @param $changeBodyType
     * @param $states
     */
    public function testArticulated($editing, $swappedTrailer, $changeBodyType, $states)
    {
        $this->setTripPersisted($editing);
        $this->tripState->getSubject()->getVehicle()->setAxleConfiguration(Vehicle::AXLE_CONFIGURATION_CHOICES[Vehicle::TRAILER_CONFIGURATION_ARTICULATED]['vehicle.axle.articulated.2.2']);

        $this->tripState->getSubject()->setIsSwappedTrailer($swappedTrailer);
        $this->tripState->getSubject()->setIsChangedBodyType($changeBodyType);

        $this->assertStateTransitions($states);
    }
}