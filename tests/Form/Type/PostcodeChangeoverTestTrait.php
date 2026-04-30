<?php

namespace App\Tests\Form\Type;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Features;
use App\Utility\PostcodeChangeoverHelper;
use Ghost\GovUkCoreBundle\Features as CoreFeatures;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

/**
 * Provides helper methods for testing forms that use PostcodeChangeoverHelper.
 */
trait PostcodeChangeoverTestTrait
{
    protected function createMockSurvey(bool $isPostcodeOnly): MockObject&Survey
    {
        $survey = $this->createMock(Survey::class);

        // If isPostcodeOnly is true, use a far-future date; otherwise use a past date
        $date = $isPostcodeOnly
            ? new \DateTime('2030-01-01')
            : new \DateTime('2020-01-01');

        $survey->method('getSurveyPeriodStart')->willReturn($date);

        return $survey;
    }

    /**
     * Create a mock SurveyResponse linked to the given Survey.
     */
    protected function createMockResponse(Survey $survey): MockObject&SurveyResponse
    {
        $response = $this->createMock(SurveyResponse::class);
        $response->method('getSurvey')->willReturn($survey);

        return $response;
    }

    /**
     * Create a mock Day linked to the given SurveyResponse.
     */
    protected function createMockDay(SurveyResponse $response): MockObject&Day
    {
        $day = $this->createMock(Day::class);
        $day->method('getResponse')->willReturn($response);

        return $day;
    }

    /**
     * Toggle POSTCODE_CHANGEOVER via $_ENV['APP_FEATURES'] and reset the
     * bundle's static feature cache so the change is visible to the next call.
     */
    protected function setPostcodeChangeoverFeature(bool $isEnabled): void
    {
        $_ENV['APP_FEATURES'] = $isEnabled ? Features::POSTCODE_CHANGEOVER : '';
        $prop = new \ReflectionProperty(CoreFeatures::class, 'enabledFeatures');
        $prop->setValue(null, []);
    }

    /**
     * Create a PostcodeChangeoverHelper with the POSTCODE_CHANGEOVER feature flag set as specified.
     */
    protected function createChangeoverHelper(bool $featureEnabled): PostcodeChangeoverHelper
    {
        $this->setPostcodeChangeoverFeature($featureEnabled);

        return new PostcodeChangeoverHelper(new NullLogger());
    }

    /**
     * Create a DayStop with mocked entity chain (Day -> Response -> Survey).
     *
     * @param bool $isPostcodeOnly Whether the survey should trigger postcode-only mode
     */
    protected function createDayStopWithMockedChain(bool $isPostcodeOnly): DayStop
    {
        $survey = $this->createMockSurvey($isPostcodeOnly);
        $response = $this->createMockResponse($survey);
        $day = $this->createMockDay($response);

        $data = $this->createPartialMock(DayStop::class, ['getDay']);
        $data->method('getDay')->willReturn($day);

        return $data;
    }

    /**
     * Create a DaySummary with mocked entity chain (Day -> Response -> Survey).
     *
     * @param bool $isPostcodeOnly Whether the survey should trigger postcode-only mode
     */
    protected function createDaySummaryWithMockedChain(bool $isPostcodeOnly): DaySummary
    {
        $survey = $this->createMockSurvey($isPostcodeOnly);
        $response = $this->createMockResponse($survey);
        $day = $this->createMockDay($response);

        $data = $this->createPartialMock(DaySummary::class, ['getDay']);
        $data->method('getDay')->willReturn($day);

        return $data;
    }
}
