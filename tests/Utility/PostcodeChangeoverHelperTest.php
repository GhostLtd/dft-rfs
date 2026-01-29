<?php

namespace App\Tests\Utility;

use App\Entity\Domestic\Survey;
use App\Features;
use App\Utility\PostcodeChangeoverHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PostcodeChangeoverHelperTest extends TestCase
{
    private function createMockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    private function createMockFeatures(bool $isEnabled): Features
    {
        $features = $this->createMock(Features::class);
        $features
            ->method('isEnabled')
            ->with(Features::POSTCODE_CHANGEOVER)
            ->willReturn($isEnabled);

        return $features;
    }

    private function createSurvey(?\DateTime $surveyPeriodStart, bool $isNorthernIreland = false): Survey
    {
        $survey = $this->createMock(Survey::class);
        $survey
            ->method('getSurveyPeriodStart')
            ->willReturn($surveyPeriodStart);
        $survey
            ->method('getIsNorthernIreland')
            ->willReturn($isNorthernIreland);

        return $survey;
    }

    public function testFeatureDisabledReturnsOldMode(): void
    {
        $features = $this->createMockFeatures(false);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2025-06-01'));

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }

    public function testNullSurveyReturnsOldMode(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), '2025-01-01');

        $this->assertFalse($helper->isPostcodeOnly(null));
    }

    public function testNullSurveyPeriodStartReturnsOldMode(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(null);

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }

    public function testNoChangeoverDateReturnsNewModeForAllSurveys(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), null);
        $survey = $this->createSurvey(new \DateTime('2020-01-01'));

        $this->assertTrue($helper->isPostcodeOnly($survey));
    }

    public function testEmptyChangeoverDateReturnsNewModeForAllSurveys(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), '');
        $survey = $this->createSurvey(new \DateTime('2020-01-01'));

        $this->assertTrue($helper->isPostcodeOnly($survey));
    }

    public function testSurveyBeforeChangeoverDateReturnsOldMode(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2024-12-31'));

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }

    public function testSurveyOnChangeoverDateReturnsNewMode(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2025-01-01'));

        $this->assertTrue($helper->isPostcodeOnly($survey));
    }

    public function testSurveyAfterChangeoverDateReturnsNewMode(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2025-01-02'));

        $this->assertTrue($helper->isPostcodeOnly($survey));
    }

    public function testChangeoverDateWithTime(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), '2025-01-01 12:00:00');
        $surveyBefore = $this->createSurvey(new \DateTime('2025-01-01 11:59:59'));
        $surveyAfter = $this->createSurvey(new \DateTime('2025-01-01 12:00:00'));

        $this->assertFalse($helper->isPostcodeOnly($surveyBefore));
        $this->assertTrue($helper->isPostcodeOnly($surveyAfter));
    }

    public function testNorthernIrelandSurveyAlwaysReturnsOldMode(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2025-06-01'), true);

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }

    public function testNorthernIrelandSurveyWithNoChangeoverDateReturnsOldMode(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), null);
        $survey = $this->createSurvey(new \DateTime('2025-06-01'), true);

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }

    public function testNonNorthernIrelandSurveyStillReturnsNewMode(): void
    {
        $features = $this->createMockFeatures(true);
        $helper = new PostcodeChangeoverHelper($features, $this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2025-06-01'), false);

        $this->assertTrue($helper->isPostcodeOnly($survey));
    }

    public function testMalformedChangeoverDateReturnsFalseAndLogsError(): void
    {
        $features = $this->createMockFeatures(true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with("Invalid POSTCODE_CHANGEOVER_DATE: 'not-a-date' — postcode feature disabled");

        $helper = new PostcodeChangeoverHelper($features, $logger, 'not-a-date');
        $survey = $this->createSurvey(new \DateTime('2025-06-01'));

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }
}
