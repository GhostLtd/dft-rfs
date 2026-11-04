<?php

namespace App\Tests\Utility;

use App\Entity\Domestic\Survey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Features;
use App\Utility\PostcodeChangeoverHelper;
use Ghost\GovUkCoreBundle\Features as CoreFeatures;
use PHPUnit\Framework\Attributes\DataProvider;
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

    // ----- IRHS -----

    private function setFeatures(string ...$features): void
    {
        $_ENV['APP_FEATURES'] = implode(',', $features);
        $this->resetStaticFeatureCache();
    }

    private function createIrhsSurvey(?\DateTime $surveyPeriodStart): InternationalSurvey
    {
        return (new InternationalSurvey())->setSurveyPeriodStart($surveyPeriodStart);
    }

    private function createIrhsHelper(?string $irhsChangeoverDate, ?LoggerInterface $logger = null): PostcodeChangeoverHelper
    {
        return new PostcodeChangeoverHelper($logger ?? $this->createMockLogger(), null, $irhsChangeoverDate);
    }

    public function testIrhsFeatureDisabledReturnsOldMode(): void
    {
        $this->setFeatures();
        $this->assertFalse($this->createIrhsHelper(null)->isIrhsPostcodeOnly($this->createIrhsSurvey(new \DateTime('2030-01-01'))));
    }

    public function testIrhsIgnoresCsrgtFeature(): void
    {
        $this->setFeatures(Features::POSTCODE_CHANGEOVER);
        $this->assertFalse($this->createIrhsHelper(null)->isIrhsPostcodeOnly($this->createIrhsSurvey(new \DateTime('2030-01-01'))));
    }

    public function testCsrgtIgnoresIrhsFeature(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), null, null);
        $this->assertFalse($helper->isPostcodeOnly($this->createSurvey(new \DateTime('2030-01-01'))));
    }

    public function testCsrgtDateDoesNotApplyToIrhs(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        // CSRGT date is in the past; IRHS date is in the future → IRHS survey must stay in old mode
        $helper = new PostcodeChangeoverHelper($this->createMockLogger(), '2020-01-01', '2029-01-01');
        $this->assertFalse($helper->isIrhsPostcodeOnly($this->createIrhsSurvey(new \DateTime('2025-01-01'))));
    }

    public function testIrhsNullSurveyReturnsOldMode(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $this->assertFalse($this->createIrhsHelper('2025-01-01')->isIrhsPostcodeOnly(null));
    }

    public function testIrhsNullSurveyPeriodStartReturnsOldMode(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $this->assertFalse($this->createIrhsHelper('2025-01-01')->isIrhsPostcodeOnly($this->createIrhsSurvey(null)));
    }

    public function testIrhsNoChangeoverDateReturnsNewMode(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $this->assertTrue($this->createIrhsHelper(null)->isIrhsPostcodeOnly($this->createIrhsSurvey(new \DateTime('2020-01-01'))));
        $this->assertTrue($this->createIrhsHelper('')->isIrhsPostcodeOnly($this->createIrhsSurvey(new \DateTime('2020-01-01'))));
    }

    public static function dataIrhsChangeoverDate(): array
    {
        return [
            'before' => ['2025-05-31', false],
            'on' => ['2025-06-01', true],
            'after' => ['2025-06-02', true],
        ];
    }

    #[DataProvider('dataIrhsChangeoverDate')]
    public function testIrhsChangeoverDate(string $surveyPeriodStart, bool $expected): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $this->assertSame($expected, $this->createIrhsHelper('2025-06-01')->isIrhsPostcodeOnly($this->createIrhsSurvey(new \DateTime($surveyPeriodStart))));
    }

    public function testIrhsMalformedChangeoverDateReturnsFalseAndLogsError(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with("Invalid APP_IRHS_POSTCODE_CHANGEOVER_DATE: 'not-a-date' — postcode feature disabled");

        $this->assertFalse($this->createIrhsHelper('not-a-date', $logger)->isIrhsPostcodeOnly($this->createIrhsSurvey(new \DateTime('2025-06-01'))));
    }

    public function testCreateIrhsPostcodeConstraint(): void
    {
        $constraint = PostcodeChangeoverHelper::createIrhsPostcodeConstraint(['trip_places']);

        $this->assertSame(PostcodeChangeoverHelper::IRHS_ALLOW_PARTIAL_POSTCODE, $constraint->allowPartial);
        $this->assertTrue($constraint->checkOutwardCode);
        $this->assertSame(['trip_places'], $constraint->groups);
    }
}
