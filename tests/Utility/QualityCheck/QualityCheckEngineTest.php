<?php

namespace App\Tests\Utility\QualityCheck;

use App\Entity\Domestic\SurveyResponse;
use App\Utility\QualityCheck\FlagSet;
use App\Utility\QualityCheck\QualityCheckEngine;
use App\Utility\QualityCheck\QualityCheckInterface;
use App\Utility\QualityCheck\QualityFlag;
use App\Utility\QualityCheck\SubjectType;
use PHPUnit\Framework\TestCase;

class QualityCheckEngineTest extends TestCase
{
    public function testNullResponseReturnsEmptyFlagSet(): void
    {
        $engine = new QualityCheckEngine([
            $this->stubCheck([$this->makeFlag('should-not-fire')]),
        ]);

        $result = $engine->run(null);
        $this->assertTrue($result->isEmpty());
    }

    public function testNoChecksReturnsEmptyFlagSet(): void
    {
        $engine = new QualityCheckEngine([]);
        $result = $engine->run($this->createMock(SurveyResponse::class));
        $this->assertTrue($result->isEmpty());
    }

    public function testAggregatesFlagsFromAllChecks(): void
    {
        $engine = new QualityCheckEngine([
            $this->stubCheck([$this->makeFlag('a'), $this->makeFlag('b')]),
            $this->stubCheck([$this->makeFlag('c')]),
            $this->stubCheck([]), // a check that doesn't fire
        ]);

        $result = $engine->run($this->createMock(SurveyResponse::class));
        $codes = array_map(fn($f) => $f->reasonCode, $result->all());
        $this->assertSame(['a', 'b', 'c'], $codes);
    }

    public function testEachCheckReceivesTheSameResponse(): void
    {
        $response = $this->createStub(SurveyResponse::class);
        $seen = [];
        $recordingCheck = new class($seen) implements QualityCheckInterface {
            public function __construct(public array &$seen) {}
            public function run(SurveyResponse $response): array
            {
                $this->seen[] = $response;
                return [];
            }
        };

        $engine = new QualityCheckEngine([$recordingCheck, $recordingCheck]);
        $engine->run($response);

        $this->assertCount(2, $seen);
        $this->assertSame($response, $seen[0]);
        $this->assertSame($response, $seen[1]);
    }

    public function testComputeAndPersistWritesCountOntoResponse(): void
    {
        $response = $this->createMock(SurveyResponse::class);
        $response->expects($this->once())->method('setQualityFlagCount')->with(3)->willReturnSelf();

        $engine = new QualityCheckEngine([
            $this->stubCheck([$this->makeFlag('a'), $this->makeFlag('b')]),
            $this->stubCheck([$this->makeFlag('c')]),
        ]);

        $set = $engine->computeAndPersist($response);
        $this->assertCount(3, $set);
    }

    public function testComputeAndPersistWritesZeroForEmptyResult(): void
    {
        $response = $this->createMock(SurveyResponse::class);
        $response->expects($this->once())->method('setQualityFlagCount')->with(0)->willReturnSelf();

        $engine = new QualityCheckEngine([]);
        $engine->computeAndPersist($response);
    }

    public function testComputeAndPersistWritesSignatureOntoResponse(): void
    {
        $flags = [$this->makeFlag('a'), $this->makeFlag('b')];
        $expectedSignature = (new FlagSet($flags))->signature();

        $response = $this->createMock(SurveyResponse::class);
        $response->method('setQualityFlagCount')->willReturnSelf();
        $response->expects($this->once())
            ->method('setQualityFlagSignature')
            ->with($expectedSignature)
            ->willReturnSelf();

        $engine = new QualityCheckEngine([$this->stubCheck($flags)]);
        $engine->computeAndPersist($response);
    }

    public function testComputeAndPersistNeverTouchesSeenState(): void
    {
        // The seen flag is owned by the recompute listener (genuine user edits), never the engine —
        // so a blanket recompute via the bulk command cannot wipe review state.
        $response = $this->createMock(SurveyResponse::class);
        $response->method('setQualityFlagCount')->willReturnSelf();
        $response->method('setQualityFlagSignature')->willReturnSelf();
        $response->expects($this->never())->method('setQualityFlagsSeen');

        $engine = new QualityCheckEngine([$this->stubCheck([$this->makeFlag('a')])]);
        $engine->computeAndPersist($response);
    }

    private function stubCheck(array $flagsToReturn): QualityCheckInterface
    {
        return new class($flagsToReturn) implements QualityCheckInterface {
            public function __construct(private array $flagsToReturn) {}
            public function run(SurveyResponse $response): array
            {
                return $this->flagsToReturn;
            }
        };
    }

    private function makeFlag(string $reasonCode): QualityFlag
    {
        return new QualityFlag(
            subjectType: SubjectType::DAY,
            subjectId: 'd1',
            reasonCode: $reasonCode,
        );
    }
}
