<?php

namespace App\Tests\EventListener\DomesticSurvey;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\EventListener\DomesticSurvey\QualityFlagRecomputeListener;
use App\Utility\QualityCheck\FlagSet;
use App\Utility\QualityCheck\QualityCheckEngine;
use App\Utility\QualityCheck\QualityFlag;
use App\Utility\QualityCheck\SubjectType;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QualityFlagRecomputeListenerTest extends TestCase
{
    private QualityCheckEngine&MockObject $engine;
    private EntityManagerInterface&MockObject $em;
    private UnitOfWork&MockObject $uow;
    private QualityFlagRecomputeListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->engine = $this->createMock(QualityCheckEngine::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->uow = $this->createMock(UnitOfWork::class);
        $this->em->method('getUnitOfWork')->willReturn($this->uow);
        $this->listener = new QualityFlagRecomputeListener($this->engine);
    }

    public function testStopChangeResolvesToOwningResponse(): void
    {
        $response = $this->createStub(SurveyResponse::class);
        $day = $this->createMock(Day::class);
        $day->method('getResponse')->willReturn($response);
        $stop = $this->createMock(DayStop::class);
        $stop->method('getDay')->willReturn($day);

        $this->scheduleUpdates([$stop]);
        $this->engine->expects($this->once())->method('computeAndPersist')->with($response);
        $this->em->expects($this->once())->method('flush');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testSummaryChangeResolvesToOwningResponse(): void
    {
        $response = $this->createStub(SurveyResponse::class);
        $day = $this->createMock(Day::class);
        $day->method('getResponse')->willReturn($response);
        $summary = $this->createMock(DaySummary::class);
        $summary->method('getDay')->willReturn($day);

        $this->scheduleInsertions([$summary]);
        $this->engine->expects($this->once())->method('computeAndPersist')->with($response);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testDayChangeResolvesToOwningResponse(): void
    {
        $response = $this->createStub(SurveyResponse::class);
        $day = $this->createMock(Day::class);
        $day->method('getResponse')->willReturn($response);

        $this->scheduleDeletions([$day]);
        $this->engine->expects($this->once())->method('computeAndPersist')->with($response);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testDeletedDayWithClearedResponseResolvesViaOriginalData(): void
    {
        // DataConsistencyListener detaches days via SurveyResponse::removeDay() (nulling Day::$response) before
        // removing them.
        $response = $this->createStub(SurveyResponse::class);
        $day = $this->createMock(Day::class);
        $day->method('getResponse')->willReturn(null);
        $this->uow->method('getOriginalEntityData')->with($day)->willReturn(['response' => $response]);

        $this->scheduleDeletions([$day]);
        $this->engine->expects($this->once())->method('computeAndPersist')->with($response);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testMultipleChangesToSameResponseDeduplicate(): void
    {
        $response = $this->createStub(SurveyResponse::class);
        $day = $this->createMock(Day::class);
        $day->method('getResponse')->willReturn($response);

        $stop1 = $this->createMock(DayStop::class);
        $stop1->method('getDay')->willReturn($day);
        $stop2 = $this->createMock(DayStop::class);
        $stop2->method('getDay')->willReturn($day);

        $this->scheduleUpdates([$stop1, $stop2]);
        $this->engine->expects($this->once())->method('computeAndPersist')->with($response);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testUnrelatedEntityIsIgnored(): void
    {
        $unrelated = $this->createStub(Survey::class);
        $this->scheduleUpdates([$unrelated]);

        $this->engine->expects($this->never())->method('computeAndPersist');
        $this->em->expects($this->never())->method('flush');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testSurveyResponseDirectChangeIsNotATrigger(): void
    {
        // SurveyResponse itself is not a trigger entity — only its descendants.
        // (We rely on this to avoid recursion when our own flush writes the
        // aggregate columns back onto the response.)
        $response = $this->createStub(SurveyResponse::class);
        $this->scheduleUpdates([$response]);

        $this->engine->expects($this->never())->method('computeAndPersist');

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testPostFlushWithoutPendingDoesNothing(): void
    {
        // onFlush wasn't called; postFlush should be a no-op.
        $this->engine->expects($this->never())->method('computeAndPersist');
        $this->em->expects($this->never())->method('flush');

        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testSecondaryFlushDuringPostFlushDoesNotRecurse(): void
    {
        // Simulate the engine's flush triggering its own onFlush via
        // the post-flush flow. The recomputing flag should make that re-entry
        // a no-op.
        $response = $this->createStub(SurveyResponse::class);
        $day = $this->createMock(Day::class);
        $day->method('getResponse')->willReturn($response);
        $stop = $this->createMock(DayStop::class);
        $stop->method('getDay')->willReturn($day);

        $this->scheduleUpdates([$stop]);

        // First flush wires in: compute called once, then $em->flush triggers
        // a nested onFlush — we simulate that nested call as part of the test.
        $this->engine->expects($this->once())->method('computeAndPersist');
        $this->em->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function () {
                // Nested onFlush during our flush — should be ignored due to recomputing flag
                $this->scheduleUpdates([]);
                $this->listener->onFlush(new OnFlushEventArgs($this->em));
            });

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testSeenClearedWhenFlagSetChanges(): void
    {
        $response = $this->createMock(SurveyResponse::class);
        $response->method('getQualityFlagsSeen')->willReturn(true);
        $response->method('getQualityFlagSignature')->willReturn('old-signature');
        $response->expects($this->once())->method('setQualityFlagsSeen')->with(false);

        // The recomputed set has a different (real) signature to the stored 'old-signature'.
        $newSet = new FlagSet([new QualityFlag(SubjectType::STOP, 's1', 'zero-distance-stop')]);
        $this->engine->method('computeAndPersist')->willReturn($newSet);

        $this->primeResponse($response);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testSeenPreservedWhenFlagSetUnchanged(): void
    {
        $newSet = new FlagSet([new QualityFlag(SubjectType::STOP, 's1', 'zero-distance-stop')]);

        $response = $this->createMock(SurveyResponse::class);
        $response->method('getQualityFlagsSeen')->willReturn(true);
        // Stored signature equals the recomputed set's signature → no change.
        $response->method('getQualityFlagSignature')->willReturn($newSet->signature());
        $response->expects($this->never())->method('setQualityFlagsSeen');

        $this->engine->method('computeAndPersist')->willReturn($newSet);

        $this->primeResponse($response);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    public function testSeenUntouchedWhenAlreadyUnseen(): void
    {
        $response = $this->createMock(SurveyResponse::class);
        $response->method('getQualityFlagsSeen')->willReturn(null);
        $response->expects($this->never())->method('setQualityFlagsSeen');

        $this->engine->method('computeAndPersist')
            ->willReturn(new FlagSet([new QualityFlag(SubjectType::STOP, 's1', 'zero-distance-stop')]));

        $this->primeResponse($response);

        $this->listener->onFlush(new OnFlushEventArgs($this->em));
        $this->listener->postFlush(new PostFlushEventArgs($this->em));
    }

    /** Wires a Day/Stop change resolving to the given response into the scheduled updates. */
    private function primeResponse(SurveyResponse $response): void
    {
        $day = $this->createMock(Day::class);
        $day->method('getResponse')->willReturn($response);
        $stop = $this->createMock(DayStop::class);
        $stop->method('getDay')->willReturn($day);

        $this->scheduleUpdates([$stop]);
    }

    private function scheduleInsertions(array $entities): void
    {
        $this->uow->method('getScheduledEntityInsertions')->willReturn($entities);
        $this->uow->method('getScheduledEntityUpdates')->willReturn([]);
        $this->uow->method('getScheduledEntityDeletions')->willReturn([]);
    }

    private function scheduleUpdates(array $entities): void
    {
        $this->uow->method('getScheduledEntityInsertions')->willReturn([]);
        $this->uow->method('getScheduledEntityUpdates')->willReturn($entities);
        $this->uow->method('getScheduledEntityDeletions')->willReturn([]);
    }

    private function scheduleDeletions(array $entities): void
    {
        $this->uow->method('getScheduledEntityInsertions')->willReturn([]);
        $this->uow->method('getScheduledEntityUpdates')->willReturn([]);
        $this->uow->method('getScheduledEntityDeletions')->willReturn($entities);
    }
}
