<?php

namespace App\Tests\Form\Type\International\Trip;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Features;
use App\Form\InternationalSurvey\Trip\OriginAndDestinationType;
use App\Tests\Form\Type\AbstractTypeTestCase;
use App\Utility\PostcodeChangeoverHelper;
use Ghost\GovUkCoreBundle\Features as CoreFeatures;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Symfony\Component\Form\PreloadedExtension;

class OriginAndDestinationTypeTest extends AbstractTypeTestCase
{
    private const string CHANGEOVER_DATE = '2026-01-01';

    #[\Override]
    protected function getExtensions(): array
    {
        return array_merge(parent::getExtensions(), [
            new PreloadedExtension([
                new OriginAndDestinationType(new PostcodeChangeoverHelper(new NullLogger(), null, self::CHANGEOVER_DATE)),
            ], []),
        ]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->setFeatures();
        parent::tearDown();
    }

    private function setFeatures(string ...$features): void
    {
        $_ENV['APP_FEATURES'] = implode(',', $features);
        (new \ReflectionProperty(CoreFeatures::class, 'enabledFeatures'))->setValue(null, []);
    }

    private function createTrip(?string $surveyPeriodStart): Trip
    {
        $survey = (new Survey())->setSurveyPeriodStart($surveyPeriodStart ? new \DateTime($surveyPeriodStart) : null);
        $response = (new SurveyResponse())->setSurvey($survey);
        $vehicle = (new Vehicle())->setSurveyResponse($response);

        return (new Trip())->setVehicle($vehicle);
    }

    private function submit(Trip $trip, string $origin, string $destination): \Symfony\Component\Form\FormInterface
    {
        $form = $this->factory->create(OriginAndDestinationType::class, $trip);
        $form->submit(['origin' => $origin, 'destination' => $destination]);

        $this->assertTrue($form->isSynchronized());
        return $form;
    }

    public static function dataFreeText(): array
    {
        return [
            'place names' => ['Southampton', 'Paris', true],
            'postcodes' => ['SO14 0YG', 'PO1 1AA', true],
            'blank origin' => ['', 'Paris', false],
            'blank destination' => ['Southampton', '', false],
        ];
    }

    #[DataProvider('dataFreeText')]
    public function testFreeTextWhenFeatureDisabled(string $origin, string $destination, bool $expectedValid): void
    {
        $this->setFeatures();
        $form = $this->submit($this->createTrip('2030-01-01'), $origin, $destination);
        $this->assertSame($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }

    #[DataProvider('dataFreeText')]
    public function testFreeTextForSurveyBeforeChangeover(string $origin, string $destination, bool $expectedValid): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $form = $this->submit($this->createTrip('2025-12-31'), $origin, $destination);
        $this->assertSame($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }

    public function testFreeTextWhenTripHasNoVehicle(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $form = $this->submit(new Trip(), 'Southampton', 'Paris');
        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));
    }

    public function testCsrgtFeatureDoesNotEnableIrhsPostcodeMode(): void
    {
        $this->setFeatures(Features::POSTCODE_CHANGEOVER);
        $form = $this->submit($this->createTrip('2030-01-01'), 'Southampton', 'Paris');
        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));
    }

    public static function dataPostcodeOnly(): array
    {
        return [
            'full postcodes' => ['SO14 0YG', 'PO1 1AA', true],
            'full postcodes, no space, lower case' => ['so140yg', 'po11aa', true],
            'NI postcode' => ['BT1 1AA', 'BT7 1NN', true],
            'partial postcodes' => ['SO14', 'PO1', PostcodeChangeoverHelper::IRHS_ALLOW_PARTIAL_POSTCODE],
            'place name origin' => ['Southampton', 'PO1 1AA', false],
            'place name destination' => ['SO14 0YG', 'Portsmouth', false],
            'blank origin' => ['', 'PO1 1AA', false],
            'blank destination' => ['SO14 0YG', '', false],
        ];
    }

    #[DataProvider('dataPostcodeOnly')]
    public function testPostcodeOnly(string $origin, string $destination, bool $expectedValid): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $form = $this->submit($this->createTrip(self::CHANGEOVER_DATE), $origin, $destination);
        $this->assertSame($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }

    public static function dataOutwardCodeCheck(): array
    {
        // Full postcodes, so these stay meaningful if partial postcodes are switched off
        return [
            'known districts' => ['SO14 0YG', 'PO1 1AA', true],
            'terminated district (BN4) is still accepted' => ['BN4 1AA', 'PO1 1AA', true],
            'unknown district origin' => ['ZZ1 1AA', 'PO1 1AA', false],
            'unknown district destination' => ['SO14 0YG', 'ZZ1 1AA', false],
        ];
    }

    #[DataProvider('dataOutwardCodeCheck')]
    public function testPostcodeOnlyWithOutwardCodeCheck(string $origin, string $destination, bool $expectedValid): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER, Features::OUTWARD_CODE_CHECK);
        $form = $this->submit($this->createTrip(self::CHANGEOVER_DATE), $origin, $destination);
        $this->assertSame($expectedValid, $form->isValid(), (string) $form->getErrors(true));
    }

    public function testUnknownDistrictAcceptedWithoutOutwardCodeCheck(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $form = $this->submit($this->createTrip(self::CHANGEOVER_DATE), 'ZZ1 1AA', 'PO1 1AA');
        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));
    }

    public function testPostcodeOnlyLabels(): void
    {
        $this->setFeatures(Features::IRHS_POSTCODE_CHANGEOVER);
        $form = $this->factory->create(OriginAndDestinationType::class, $this->createTrip(self::CHANGEOVER_DATE));

        $this->assertSame('international.trip.places.origin.postcode-only.label', $form->get('origin')->getConfig()->getOption('label'));
        $this->assertSame('international.trip.places.destination.postcode-only.label', $form->get('destination')->getConfig()->getOption('label'));
    }

    public function testFreeTextLabels(): void
    {
        $this->setFeatures();
        $form = $this->factory->create(OriginAndDestinationType::class, $this->createTrip(self::CHANGEOVER_DATE));

        $this->assertSame('international.trip.places.origin.label', $form->get('origin')->getConfig()->getOption('label'));
        $this->assertSame('international.trip.places.destination.label', $form->get('destination')->getConfig()->getOption('label'));
    }
}
