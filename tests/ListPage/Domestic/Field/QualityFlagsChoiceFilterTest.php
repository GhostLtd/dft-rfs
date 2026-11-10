<?php

namespace App\Tests\ListPage\Domestic\Field;

use App\ListPage\Domestic\Field\QualityFlagsChoiceFilter;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QualityFlagsChoiceFilterTest extends TestCase
{
    public function testAnyFiltersToResponsesWithFlags(): void
    {
        [$qb, $wheres, $params] = $this->recordingQueryBuilder();

        $this->filter()->addFilterCondition($qb, QualityFlagsChoiceFilter::CHOICE_ANY);

        $this->assertSame(['response.qualityFlagCount > 0'], $wheres());
        $this->assertSame([], $params());
    }

    public function testUnseenFiltersToFlaggedUnreviewedResponses(): void
    {
        [$qb, $wheres, $params] = $this->recordingQueryBuilder();

        $this->filter()->addFilterCondition($qb, QualityFlagsChoiceFilter::CHOICE_UNSEEN);

        $this->assertSame([
            'response.qualityFlagCount > 0',
            'response.qualityFlagsSeen IS NULL OR response.qualityFlagsSeen = :qualityFlagsSeen',
        ], $wheres());
        $this->assertSame(['qualityFlagsSeen' => 0], $params());
    }

    public function testSeenFiltersToFlaggedReviewedResponses(): void
    {
        [$qb, $wheres, $params] = $this->recordingQueryBuilder();

        $this->filter()->addFilterCondition($qb, QualityFlagsChoiceFilter::CHOICE_SEEN);

        $this->assertSame([
            'response.qualityFlagCount > 0',
            'response.qualityFlagsSeen = :qualityFlagsSeen',
        ], $wheres());
        $this->assertSame(['qualityFlagsSeen' => 1], $params());
    }

    public function testNoneFiltersToResponsesWithoutFlags(): void
    {
        [$qb, $wheres, $params] = $this->recordingQueryBuilder();

        $this->filter()->addFilterCondition($qb, QualityFlagsChoiceFilter::CHOICE_NONE);

        $this->assertSame(['response.qualityFlagCount IS NULL OR response.qualityFlagCount = 0'], $wheres());
        $this->assertSame([], $params());
    }

    public function testUnknownChoiceLeavesQueryBuilderUnchanged(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->never())->method('andWhere');

        $result = $this->filter()->addFilterCondition($qb, 'gibberish');
        $this->assertSame($qb, $result);
    }

    public function testFormChoicesCoverAllStates(): void
    {
        $options = $this->filter()->getFormOptions();

        $this->assertArrayHasKey('choices', $options);
        $this->assertSame([
            'Any' => 'any',
            'Unreviewed' => 'unseen',
            'Reviewed' => 'seen',
            'None' => 'none',
        ], $options['choices']);
    }

    private function filter(): QualityFlagsChoiceFilter
    {
        return new QualityFlagsChoiceFilter('Quality flags');
    }

    /**
     * @return array{0: QueryBuilder, 1: callable(): array, 2: callable(): array}
     */
    private function recordingQueryBuilder(): array
    {
        $qb = $this->createMock(QueryBuilder::class);
        $wheres = [];
        $params = [];

        $qb->method('andWhere')->willReturnCallback(function (string $expr) use ($qb, &$wheres) {
            $wheres[] = $expr;
            return $qb;
        });
        $qb->method('setParameter')->willReturnCallback(function (string $key, $value) use ($qb, &$params) {
            $params[$key] = $value;
            return $qb;
        });

        return [
            $qb,
            function () use (&$wheres) { return $wheres; },
            function () use (&$params) { return $params; },
        ];
    }
}
