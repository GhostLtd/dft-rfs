<?php

namespace App\Tests\Utility;

use App\Entity\Domestic\Survey;
use App\Features;
use App\Utility\PostcodeChangeoverHelper;
use Ghost\GovUkCoreBundle\Features as CoreFeatures;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PostcodeChangeoverHelperTest extends TestCase
{
    /**
     * Bundle's Features class caches resolved feature states statically.
     * Reset between tests so APP_FEATURES env-var changes are picked up.
     */
    private function resetStaticFeatureCache(): void
    {
        $prop = new \ReflectionProperty(CoreFeatures::class, 'enabledFeatures');
        $prop->setValue(null, []);
    }

    private function setFeatureEnvVar(bool $postcodeChangeoverEnabled): void
    {
        $_ENV['APP_FEATURES'] = $postcodeChangeoverEnabled ? Features::POSTCODE_CHANGEOVER : '';
        $this->resetStaticFeatureCache();
    }

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetStaticFeatureCache();
    }

    #[\Override]
    protected function tearDown(): void
    {
        unset($_ENV['APP_FEATURES']);
        $this->resetStaticFeatureCache();
        parent::tearDown();
    }

    private function createMockLogger(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
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
        $this->setFeatureEnvVar(false);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2025-06-01'));

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }

    public function testNullSurveyReturnsOldMode(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '2025-01-01');

        $this->assertFalse($helper->isPostcodeOnly(null));
    }

    public function testNullSurveyPeriodStartReturnsOldMode(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(null);

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }

    public function testNoChangeoverDateReturnsNewModeForAllSurveys(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), null);
        $survey = $this->createSurvey(new \DateTime('2020-01-01'));

        $this->assertTrue($helper->isPostcodeOnly($survey));
    }

    public function testEmptyChangeoverDateReturnsNewModeForAllSurveys(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '');
        $survey = $this->createSurvey(new \DateTime('2020-01-01'));

        $this->assertTrue($helper->isPostcodeOnly($survey));
    }

    public function testSurveyBeforeChangeoverDateReturnsOldMode(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2024-12-31'));

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }

    public function testSurveyOnChangeoverDateReturnsNewMode(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2025-01-01'));

        $this->assertTrue($helper->isPostcodeOnly($survey));
    }

    public function testSurveyAfterChangeoverDateReturnsNewMode(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2025-01-02'));

        $this->assertTrue($helper->isPostcodeOnly($survey));
    }

    public function testChangeoverDateWithTime(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '2025-01-01 12:00:00');
        $surveyBefore = $this->createSurvey(new \DateTime('2025-01-01 11:59:59'));
        $surveyAfter = $this->createSurvey(new \DateTime('2025-01-01 12:00:00'));

        $this->assertFalse($helper->isPostcodeOnly($surveyBefore));
        $this->assertTrue($helper->isPostcodeOnly($surveyAfter));
    }

    public function testNorthernIrelandSurveyAlwaysReturnsOldMode(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2025-06-01'), true);

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }

    public function testNorthernIrelandSurveyWithNoChangeoverDateReturnsOldMode(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), null);
        $survey = $this->createSurvey(new \DateTime('2025-06-01'), true);

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }

    public function testNonNorthernIrelandSurveyStillReturnsNewMode(): void
    {
        $this->setFeatureEnvVar(true);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '2025-01-01');
        $survey = $this->createSurvey(new \DateTime('2025-06-01'), false);

        $this->assertTrue($helper->isPostcodeOnly($survey));
    }

    public function testMalformedChangeoverDateReturnsFalseAndLogsError(): void
    {
        $this->setFeatureEnvVar(true);
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with("Invalid APP_POSTCODE_CHANGEOVER_DATE: 'not-a-date' — postcode feature disabled");

        $helper = new PostcodeChangeoverHelper($logger, 'not-a-date');
        $survey = $this->createSurvey(new \DateTime('2025-06-01'));

        $this->assertFalse($helper->isPostcodeOnly($survey));
    }
}
