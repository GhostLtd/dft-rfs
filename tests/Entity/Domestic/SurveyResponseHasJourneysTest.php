<?php

namespace App\Tests\Entity\Domestic;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\SurveyResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SurveyResponseHasJourneysTest extends TestCase
{
    public static function dayProvider(): array
    {
        return [
            'no days' => [[], false],
            'stop day with a stop' => [[fn() => self::stopDay(1)], true],
            'stop day without stops' => [[fn() => self::stopDay(0)], false],
            'summary day with a summary' => [[fn() => self::summaryDay(true)], true],
            'summary day without a summary' => [[fn() => self::summaryDay(false)], false],
            'empty stop day alongside a stop day' => [[fn() => self::stopDay(0), fn() => self::stopDay(2)], true],
            'empty stop day alongside an empty summary day' => [[fn() => self::stopDay(0), fn() => self::summaryDay(false)], false],
        ];
    }

    /** @param array<\Closure(): Day> $dayFactories */
    #[DataProvider('dayProvider')]
    public function testHasJourneys(array $dayFactories, bool $expected): void
    {
        $response = new SurveyResponse();
        foreach ($dayFactories as $number => $dayFactory) {
            $response->addDay($dayFactory()->setNumber($number + 1));
        }

        $this->assertSame($expected, $response->hasJourneys());
    }

    public function testExportCountsTakePrecedenceOverDays(): void
    {
        $response = (new SurveyResponse())->addDay(self::stopDay(1)->setNumber(1));
        $response->_stopCountForExport = 0;
        $response->_summaryCountForExport = 0;

        $this->assertFalse($response->hasJourneys());
    }

    private static function stopDay(int $stopCount): Day
    {
        $day = (new Day())->setHasMoreThanFiveStops(false);
        for ($i = 0; $i < $stopCount; $i++) {
            $day->addStop(new DayStop());
        }
        return $day;
    }

    private static function summaryDay(bool $withSummary): Day
    {
        $day = (new Day())->setHasMoreThanFiveStops(true);
        if ($withSummary) {
            $day->setSummary(new DaySummary());
        }
        return $day;
    }
}
