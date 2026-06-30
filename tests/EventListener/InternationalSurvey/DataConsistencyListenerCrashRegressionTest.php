<?php

namespace App\Tests\EventListener\InternationalSurvey;

use App\Entity\Embeddable\Distance;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Entity\Vehicle as VehicleLookup;
use App\Tests\DataFixtures\International\TripFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Regression test for the FK-1451 / 500 crash documented as hgv-zero-vehicle-delete-500.
 *
 * Previously, when the estimated HGV journey count was 0, DataConsistencyListener::onFlush
 * deleted every vehicle on the response mid-flush. If a brand-new vehicle + trip was being
 * inserted in that same flush (the add-controllers call setVehicle but never addTrip), the
 * new trip referenced the vehicle being deleted yet escaped cascade-remove, producing
 * `SQLSTATE[23000] 1451 Cannot delete or update a parent row`.
 *
 * The estimate is now understood as forward-looking (the 12 months following the survey
 * period), so a still-active firm estimating 0 future HGV trips keeps its vehicles and can
 * record trips made during the period. This test reproduces the old scenario and asserts
 * the data is now retained.
 *
 * Note: the production symptom was the MySQL FK error; the test DB is SQLite, which doesn't
 * enforce that FK, so we assert the underlying *cause* the fix removes — the listener
 * wrongly deleting the response's vehicles when the HGV estimate is 0. With the old
 * predicate this flush left zero vehicles; with the fix both vehicles survive.
 */
class DataConsistencyListenerCrashRegressionTest extends KernelTestCase
{
    protected AbstractDatabaseTool $databaseTool;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }

    public function testZeroHgvEstimateWhileAddingVehicleAndTripRetainsData(): void
    {
        $references = $this->databaseTool->loadFixtures([TripFixtures::class])->getReferenceRepository();

        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        /** @var SurveyResponse $response */
        $response = $references->getReference('response:int', SurveyResponse::class);

        // Still active, but the firm now estimates zero HGV trips for the following year.
        $response->setAnnualInternationalHGVJourneyCount(0);

        // Add a new vehicle + trip in the same flush — the trip is wired via setVehicle only
        // (never addTrip), exactly as the add-controllers do, which is what triggered the
        // mid-flush cascade-remove miss.
        $newVehicle = (new Vehicle())
            ->setRegistrationMark('BB02 DEF')
            ->setAxleConfiguration(333)
            ->setTrailerConfiguration(300)
            ->setBodyType(VehicleLookup::BODY_TYPE_CURTAIN_SIDED)
            ->setCarryingCapacity(35000)
            ->setGrossWeight(28000)
            ->setOperationType(VehicleLookup::OPERATION_TYPE_ON_OWN_ACCOUNT);
        $response->addVehicle($newVehicle);

        $newTrip = (new Trip())
            ->setVehicle($newVehicle)
            ->setOutboundDate(new \DateTime('2021-05-01'))
            ->setReturnDate(new \DateTime('2021-05-03'))
            ->setIsChangedBodyType(false)
            ->setIsSwappedTrailer(false)
            ->setOutboundUkPort('Portfoot')
            ->setOutboundForeignPort('Waspwerp')
            ->setReturnForeignPort('Waspwerp')
            ->setReturnUkPort('Portfoot')
            ->setRoundTripDistance((new Distance())->setUnit(Distance::UNIT_MILES)->setValue('1450.0'))
            ->setCountriesTransitted(['FR', 'BE'])
            ->setReturnWasEmpty(false)
            ->setReturnWasLimitedBySpace(false)
            ->setReturnWasLimitedByWeight(false)
            ->setOutboundWasEmpty(false)
            ->setOutboundWasLimitedBySpace(false)
            ->setOutboundWasLimitedByWeight(false)
            ->setOrigin('Chichester')
            ->setDestination('Chichester');

        $em->persist($newVehicle);
        $em->persist($newTrip);

        // Pre-fix this flush threw the FK violation; it should now succeed.
        $em->flush();
        $em->clear();

        // Both the original fixture vehicle and the newly-added one survive.
        $reloaded = $em->getRepository(SurveyResponse::class)->find($response->getId());
        $this->assertCount(2, $reloaded->getVehicles());
        $this->assertSame(1, $em->getRepository(Trip::class)->count(['vehicle' => $newVehicle->getId()]));
    }
}
