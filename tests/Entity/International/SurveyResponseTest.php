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

    /**
     * Tests shouldSurveyBeSubmittedWithoutJourneys() which returns true only if
     * isNoLongerActive() is true (ceased-trading, only-domestic-work, no-road-haulage).
     *
     * An estimated HGV journey count of 0 no longer ends the survey: an active firm may
     * still go on to add vehicles (or submit an empty survey), so the journey counts do
     * not affect this method.
     */
    public function dataShouldSurveyBeSubmittedWithoutJourneys(): array
    {
        return [
            'still-active with positive HGV' => [
                SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE, 100, 50, false
            ],
            'still-active with zero HGV and zero LGV' => [
                SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE, 0, 0, false
            ],
            'still-active with zero HGV and positive LGV (LGV-only firm)' => [
                SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE, 0, 100, false
            ],
            'ceased-trading (journey counts ignored)' => [
                SurveyResponse::ACTIVITY_STATUS_CEASED_TRADING, null, null, true
            ],
            'only-domestic-work (journey counts ignored)' => [
                SurveyResponse::ACTIVITY_STATUS_ONLY_DOMESTIC_WORK, null, null, true
            ],
        ];
    }

    /** @dataProvider dataShouldSurveyBeSubmittedWithoutJourneys */
    public function testShouldSurveyBeSubmittedWithoutJourneys(
        string $activityStatus,
        ?int $hgvCount,
        ?int $lgvCount,
        bool $expected
    ): void {
        $response = (new SurveyResponse())
            ->setActivityStatus($activityStatus);

        // Only set journey counts for still-active status (non-active clears them)
        if ($activityStatus === SurveyResponse::ACTIVITY_STATUS_STILL_ACTIVE) {
            $response->setAnnualInternationalHGVJourneyCount($hgvCount);
            $response->setAnnualInternationalLGVJourneyCount($lgvCount);
        }

        $this->assertEquals($expected, $response->shouldSurveyBeSubmittedWithoutJourneys());
    }
}