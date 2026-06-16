<?php

namespace App\Tests\Entity\International;

use App\Entity\International\Survey;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SurveyTest extends TestCase
{
    /**
     * The estimate-period label describes the 12 months following the survey period:
     * it starts the day after the survey period ends, and is suffixed with the
     * <startYear>-<2-digit next year> range.
     */
    public static function dataEstimatePeriodLabel(): array
    {
        return [
            'period ending 31 Dec 2026' => ['2026-12-31', '1 January 2027-28'],
            'period ending mid-year' => ['2026-06-30', '1 July 2026-27'],
            'day after end crosses into next decade' => ['2029-12-31', '1 January 2030-31'],
        ];
    }

    #[DataProvider('dataEstimatePeriodLabel')]
    public function testEstimatePeriodLabel(string $surveyPeriodEnd, string $expected): void
    {
        $survey = (new Survey())
            ->setSurveyPeriodEnd(new \DateTime($surveyPeriodEnd));

        $this->assertEquals($expected, $survey->getEstimatePeriodLabel());
    }

    public function testEstimatePeriodLabelIsNullWhenPeriodEndUnset(): void
    {
        $this->assertNull((new Survey())->getEstimatePeriodLabel());
    }
}
