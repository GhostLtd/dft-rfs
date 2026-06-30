<?php

namespace App\Tests\EventListener\InternationalSurvey;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Vehicle;
use App\EventListener\InternationalSurvey\DataConsistencyListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DataConsistencyListenerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UnitOfWork&MockObject $uow;
    private DataConsistencyListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->em->method('getUnitOfWork')->willReturn($this->uow);
        $this->em->method('getClassMetadata')->willReturn($this->createMock(ClassMetadata::class));
        $this->listener = new DataConsistencyListener();
    }

    public function testActiveFirmWithZeroHgvJourneysKeepsItsVehicles(): void
    {
        $vehicle = $this->buildSurveyWithVehicle(SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE, 0);

        // An active firm that estimates zero HGV trips may still add vehicles, so nothing
        // should be removed.
        $this->em->expects($this->never())->method('remove');

        $this->scheduleUpdates([$vehicle]);
        $this->listener->onFlush(new OnFlushEventArgs($this->em));

        $this->assertTrue($vehicle->getSurveyResponse()->getVehicles()->contains($vehicle));
    }

    public function testInactiveFirmStillHasItsVehiclesDeleted(): void
    {
        $vehicle = $this->buildSurveyWithVehicle(SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING, null);
        $response = $vehicle->getSurveyResponse();

        $this->em->expects($this->once())->method('remove')->with($vehicle);

        $this->scheduleUpdates([$vehicle]);
        $this->listener->onFlush(new OnFlushEventArgs($this->em));

        $this->assertFalse($response->getVehicles()->contains($vehicle));
    }

    private function buildSurveyWithVehicle(string $activityStatus, ?int $hgvJourneyCount): Vehicle
    {
        $response = (new SurveyResponse())
            ->setActivityStatus($activityStatus)
            ->setAnnualInternationalHGVJourneyCount($hgvJourneyCount);

        (new Survey())->setResponse($response);

        $vehicle = new Vehicle();
        $response->addVehicle($vehicle);

        return $vehicle;
    }

    private function scheduleUpdates(array $entities): void
    {
        $this->uow->method('getScheduledEntityInsertions')->willReturn([]);
        $this->uow->method('getScheduledEntityUpdates')->willReturn($entities);
    }
}
