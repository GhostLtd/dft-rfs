<?php

namespace App\Tests\Form\Type;

use App\DTO\RoRo\OperatorRoute;
use App\Entity\RoRo\Country;
use App\Entity\RoRo\Survey;
use App\Entity\RoRo\VehicleCount;
use App\Form\RoRo\DataEntryType;
use App\Repository\RoRo\CountryRepository;
use App\Repository\RoRo\SurveyRepository;
use App\Repository\Route\RouteRepository;
use App\Utility\RoRo\SurveyCreationHelper;
use App\Utility\RoRo\VehicleCountHelper;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Form\Extension\ConditionalTypeExtension;
use Ghost\GovUkFrontendBundle\Form\Extension\FormTypeExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatorInterface;

class DataEntryTypeTest extends TypeTestCase
{
    protected SurveyCreationHelper $surveyCreationHelper;
    protected VehicleCountHelper $vehicleCountHelper;

    protected function getCountryCodes(): array
    {
        return ['AL', 'AT', 'BY', 'BE', 'BA', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'GE', 'DE', 'GR', 'HU', 'IS', 'IE', 'IT', 'XK', 'LV', 'LT', 'LU', 'MT', 'MD', 'ME', 'NL', 'MK', 'NO', 'PL', 'PT', 'RO', 'RU', 'RS', 'SK', 'SI', 'ES', 'SE', 'CH', 'TR', 'UA', 'GB'];
    }

    protected function getAllCodes(): array
    {
        return array_merge(
            $this->getCountryCodes(),
            [VehicleCount::OTHER_CODE_OTHER, VehicleCount::OTHER_CODE_UNKNOWN, VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS]
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock
            ->method('getReference')
            ->willReturnCallback(fn(string $class, string $id) => (new $class)->setId($id));

        $countryRepositoryMock = $this->createMock(CountryRepository::class);
        $countryRepositoryMock
            ->method('findAll')
            ->willReturnCallback(function () {
                return array_map(
                    fn(string $code) => (new Country())->setCode($code)->setId(Uuid::v4()),
                    $this->getCountryCodes()
                );
            });

        $this->surveyCreationHelper = new SurveyCreationHelper(
            $entityManagerMock,
            $this->createMock(LoggerInterface::class),
            $this->createMock(RouteRepository::class),
            $this->createMock(SurveyRepository::class),
            $countryRepositoryMock,
        );

        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock
            ->method('trans')
            ->willReturnCallback(function(string $string) {
                return match ($string) {
                    'roro.survey.vehicle-count.others.XK' => 'Kosovo',
                    'roro.survey.vehicle-count.others.other' => 'Other country',
                    'roro.survey.vehicle-count.others.unknown' => 'Unknown country',
                    'roro.survey.vehicle-count.others.trailers' => 'Unaccompanied trailers',
                    default => null
                };
            });

        $this->vehicleCountHelper = new VehicleCountHelper($translatorMock);
    }

    protected function getTypeExtensions(): array
    {
        return [
            new ConditionalTypeExtension(),
            new FormTypeExtension(),
        ];
    }

    protected function getExtensions(): array
    {
        return [
            // Not testing validation, but will otherwise explode due to 'constraints' option usage in form
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function dataMapper(): array
    {
        $testCases = [
            'Simple' => ["Spain\t   (ES)\t\t\t292", $this->zeroesApartFrom(['ES' => 292])],
            'Simple (multiple)' => ["United Kingdom\t(GB)\t100\nFrance\t(FR)  300\r\nGermany  (DE)\t\t\t201", $this->zeroesApartFrom(['GB' => 100, 'FR' => 300, 'DE' => 201])],
            'Other countries - variation #1' => ["Other 202", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_OTHER => 202])],
            'Other countries - variation #2' => ["Other country 203", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_OTHER => 203])],
            'Other countries - variation #3' => ["Other countries 204", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_OTHER => 204])],
            'Unaccompanied trailers - variation #1' => ["Unaccompanied 502", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 502])],
            'Unaccompanied trailers - variation #2' => ["Unaccompanied trailer 503", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 503])],
            'Unaccompanied trailers - variation #3' => ["Unaccompanied trailers 504", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 504])],
            'Unknown countries - variation #1' => ["Unknown 302", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_UNKNOWN => 302])],
            'Unknown countries - variation #2' => ["Unknown country 303", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_UNKNOWN => 303])],
            'Unknown countries - variation #3' => ["Unknown countries 304", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_UNKNOWN => 304])],
        ];

        $oldCountryNames = [
            'Bosnia' => 'BA',
            'Czech Republic' => 'CZ',
            'Eire' => 'IE',
            'FYR of Macedonia' => 'MK',
            'Turkey' => 'TR',
        ];

        foreach($oldCountryNames as $oldCountryName => $expectedCode) {
            $testCases["Old country name with code: $oldCountryName"] = ["$oldCountryName 105", $this->zeroesApartFrom([$expectedCode => 105])];
            $testCases["Old country name without code: $oldCountryName"] = ["Wibble ({$expectedCode}) 106", $this->zeroesApartFrom([$expectedCode => 106])];
        }

        foreach($this->getCountryCodes() as $expectedCode) {
            $name = match($expectedCode) {
                'XK' => 'Kosovo',
                default => Countries::getName($expectedCode),
            };

            $testCases["Country with code: {$name}"] = ["{$name} 107", $this->zeroesApartFrom([$expectedCode => 107])];
            $testCases["Country without code: {$name}"] = ["Wibble ({$expectedCode}) 108", $this->zeroesApartFrom([$expectedCode => 108])];
        }

        return $testCases;
    }

    /**
     * @dataProvider dataMapper
     */
    public function testMapper(string $input, array $expectedCounts): void
    {
        $survey = $this->getSurvey();
        $form = $this->submitForm($survey, $input);

        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();
        $this->assertInstanceOf(Survey::class, $data);

        foreach ($expectedCounts as $code => $expectedCount) {
            $found = false;
            foreach ($data->getVehicleCounts() as $vehicleCount) {
                if ($vehicleCount->getCountryCode() === $code || $vehicleCount->getOtherCode() === $code) {
                    $this->assertEquals($expectedCount, $vehicleCount->getVehicleCount(), "Count mismatch for '{$code}'");
                    $found = true;
                    break;
                }
            }

            $this->assertTrue($found, "No matching entry for '{$code}'");
        }
    }

    public function dataTotals(): array
    {
        return [
            ["Nothing 100", null, null],
            ["Total vehicles 101", 101, null],
            ["Total powered vehicles 102", null, 102],
            ["TOTAL - VEHICLES 103", 103, null],
        ];
    }

    /**
     * @dataProvider dataTotals
     */
    public function testTotals(string $input, ?int $expectedTotalVehicles, ?int $expectedTotalPoweredVehicles): void
    {
        $survey = $this->getSurvey();
        $this->assertNull($survey->getDataEntryDebugInfo());

        $this->submitForm($survey, $input);

        $debug = $survey->getDataEntryDebugInfo();
        $this->assertEquals($expectedTotalVehicles, $debug->getTotalVehicles());
        $this->assertEquals($expectedTotalPoweredVehicles, $debug->getTotalPoweredVehicles());
    }

    // -----

    protected function getSurvey(): Survey
    {
        $operatorRoute = new OperatorRoute(111, 222);
        $survey = $this->surveyCreationHelper->createSurvey(new \DateTime('now'), $operatorRoute);
        $this->vehicleCountHelper->setVehicleCountLabels($survey->getVehicleCounts());
        return $survey;
    }

    protected function submitForm(Survey $survey, string $input): FormInterface
    {
        $form = $this->factory->create(DataEntryType::class, $survey);
        $form->submit(['data' => $input]);

        return $form;
    }

    protected function zeroesApartFrom(array $expected): array
    {
        $allZeroes = [];
        foreach($this->getAllCodes() as $code) {
            $allZeroes[$code] = 0;
        }

        return array_merge($allZeroes, $expected);
    }
}