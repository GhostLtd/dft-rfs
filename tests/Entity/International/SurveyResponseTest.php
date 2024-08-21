<?php

namespace App\Tests\Entity\International;

use App\Entity\International\SurveyResponse;
use PHPUnit\Framework\TestCase;

class SurveyResponseTest extends TestCase
{
    public function dataNoLongerActive(): array
    {
        return [
            [SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK, true],
            [SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING, true],
            [SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE, false],
        ];
    }

    /** @dataProvider dataNoLongerActive */
    public function testNoLongerActive(string $activityStatus, bool $expectedNoLongerActive): void
    {
        $response = (new SurveyResponse())
            ->setActivityStatus($activityStatus);

        $this->assertEquals($expectedNoLongerActive, $response->isNoLongerActive());
    }
}