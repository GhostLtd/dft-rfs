<?php

namespace App\Tests\Form\Type\RoRo;

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

    #[\Override]
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
            ->willReturnCallback(fn() => array_map(
                fn(string $code) => (new Country())->setCode($code)->setId(Uuid::v4()),
                $this->getCountryCodes()
            ));

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
            ->willReturnCallback(fn(string $string) => match ($string) {
                'roro.survey.vehicle-count.others.XK' => 'Kosovo',
                'roro.survey.vehicle-count.others.other' => 'Other country',
                'roro.survey.vehicle-count.others.unknown' => 'Unknown country',
                'roro.survey.vehicle-count.others.trailers' => 'Unaccompanied trailers',
                default => null
            });

        $this->vehicleCountHelper = new VehicleCountHelper($translatorMock);
    }

    #[\Override]
    protected function getTypeExtensions(): array
    {
        return [
            new ConditionalTypeExtension(),
            new FormTypeExtension(),
        ];
    }

    #[\Override]
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
            'Unaccompanied trailers - variation #4' => ["unacc 505", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 505])],
            'Unknown countries - variation #1' => ["Unknown 302", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_UNKNOWN => 302])],
            'Unknown countries - variation #2' => ["Unknown country 303", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_UNKNOWN => 303])],
            'Unknown countries - variation #3' => ["Unknown countries 304", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_UNKNOWN => 304])],
            'Commas in number' => ["Portugal\t   (PT)\t\t\t2,396", $this->zeroesApartFrom(['PT' => 2396])],

            // Kosovo copies strangely out of the sample spreadsheets (further comments in DataEntryDataMapper.php)
            'Kosovo weirdness' => ["   Kosovo\t\tKosovo\t\t\t 101", $this->zeroesApartFrom(['XK' => 101])],

            'Country codes only' => ["     PT 1234", $this->zeroesApartFrom(['PT' => 1234])],
            'Country codes only - brackets' => [" (PT) 2345", $this->zeroesApartFrom(['PT' => 2345])],
            'Country names only' => [" Portugal 3456", $this->zeroesApartFrom(['PT' => 3456])],

            'Non-survey country codes - variation #1' => ["ZA 4567", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_OTHER => 4567])],
            'Non-survey country codes - variation #2' => ["Other 123 ZA 4567", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_OTHER => 4567 + 123])],
            'Non-survey country codes - variation #3' => ["Other 123 Latvia (LV) 101 ZA 4567", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_OTHER => 4567 + 123, 'LV' => 101])],
            'Non-survey country names - variation #1' => ["South Africa 4567", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_OTHER => 4567])],
            'Non-survey country names - variation #2' => ["Other 123 South Africa 4567", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_OTHER => 4567 + 123])],
            'Non-survey country names - variation #3' => ["Other 123 Latvia (LV) 101 South Africa 4567", $this->zeroesApartFrom([VehicleCount::OTHER_CODE_OTHER => 4567 + 123, 'LV' => 101])],

            'Sample spreadsheet - variation #1 (PO:3/March)' => [$this->getSampleSpreadsheetOne(), $this->zeroesApartFrom([
                    'AL' => 1, 'AT' => 29, 'BY' => 515, 'BE' => 159, 'BA' => 80, 'BG' => 2607, 'HR' => 89, 'CY' => 4,
                    'CZ' => 356,  'DK' => 4, 'IE' => 109, 'EE' => 18, 'FI' => 1, 'FR' => 314, 'GE' => 4, 'DE' => 264,
                    'GR' => 46, 'HU' => 2191, 'IS' => 1, 'IT' => 199, 'LV' => 379, 'LT' => 3229, 'LU' => 14, 'MK' => 162,
                    'MT' => 2, 'MD' => 10, 'ME' => 3, 'NL' => 98, 'NO' => 1, 'PL' => 9350, 'PT' => 1251, 'RO' => 7432,
                    'RU' => 35, 'RS' => 113, 'SK' => 167, 'SI' => 125, 'ES' => 2254, 'SE' => 30, 'CH' => 8, 'TR' => 227,
                    'UA' => 1306, 'GB' => 1308,
                    VehicleCount::OTHER_CODE_OTHER => 5457,
                    VehicleCount::OTHER_CODE_UNKNOWN => 209,
                    VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 3,
                ]),
                40161,
                40164,
                [],
                [
                    // The unused header
                    'Country of Vehicle Registration						Powered Vehicles						Country of Vehicle Registration						Powered Vehicles',
                ]
            ],

            'Sample spreadsheet - variation #2 (PO:4/Import col G+H)' => [$this->getSampleSpreadsheetTwo(), $this->zeroesApartFrom([
                    'AL' => 1, 'AT' => 29, 'BY' => 515, 'BE' => 159, 'BA' => 80, 'BG' => 2607, 'HR' => 89, 'CY' => 4,
                    'CZ' => 356,  'DK' => 4, 'IE' => 109, 'EE' => 18, 'FI' => 1, 'FR' => 314, 'GE' => 4, 'DE' => 264,
                    'GR' => 46, 'HU' => 2191, 'IS' => 1, 'IT' => 199, 'LV' => 379, 'LT' => 3229, 'LU' => 14, 'MK' => 162,
                    'MT' => 2, 'MD' => 10, 'ME' => 3, 'NL' => 98, 'NO' => 1, 'PL' => 9350, 'PT' => 1251, 'RO' => 7432,
                    'RU' => 35, 'RS' => 113, 'SK' => 167, 'SI' => 125, 'ES' => 2254, 'SE' => 30, 'CH' => 8, 'TR' => 227,
                    'UA' => 1306, 'GB' => 1308,
                    VehicleCount::OTHER_CODE_OTHER => 5457,
                    VehicleCount::OTHER_CODE_UNKNOWN => 209,
                    VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 3,
                ]),
                null,
                40164,
                [],
                [
                    // Unused row between the month header and the data
                    'Country	Movements',
                ]
            ],

            'Sample spreadsheet - variation #3 (ET:1)' => [$this->getSampleSpreadsheetThree(), $this->zeroesApartFrom([
                    'AT' => 911, 'BE' => 3613, 'BG' => 48, 'CZ' => 652, 'DK' => 370, 'IE' => 397, 'EE' => 37, 'FI' => 1,
                    'FR' => 3345, 'DE' => 3869, 'GR' => 77, 'HU' => 330, 'IT' => 987, 'LV' => 139, 'LT' => 1032, 'LU' => 467,
                    'MK' => 199, 'MT' => 8, 'ME' => 15, 'NL' => 7582, 'PL' => 2148, 'PT' => 609, 'RO' => 1028, 'SK' => 625,
                    'SI' => 60, 'ES' => 6526, 'SE' => 25, 'CH' => 5, 'TR' => 85, 'GB' => 8273,
                    VehicleCount::OTHER_CODE_OTHER => 36,
                    VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 127,
                ]),
                43499,
                null,
                [],
                []
            ],

            'Sample spreadsheet - variation #4 (ET:2)' => [$this->getSampleSpreadsheetFour(), $this->zeroesApartFrom([
                    'AT' => 911, 'BE' => 3613, 'BG' => 48, 'CZ' => 652, 'DK' => 370, 'IE' => 397, 'EE' => 37, 'FI' => 1,
                    'FR' => 3345, 'DE' => 3869, 'GR' => 77, 'HU' => 330, 'IT' => 987, 'LV' => 139, 'LT' => 1032, 'LU' => 467,
                    'MK' => 199, 'MT' => 8, 'ME' => 15, 'NL' => 7582, 'PL' => 2148, 'PT' => 609, 'RO' => 1028, 'SK' => 625,
                    'SI' => 60, 'ES' => 6526, 'SE' => 25, 'CH' => 5, 'TR' => 85, 'GB' => 8273,
                    VehicleCount::OTHER_CODE_OTHER => 36,
                    VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 127,
                ]),
                43499,
                43626,
                [],
                [
                    // The unused header
                    'Country of Vehicle Registration						Powered Vehicles							Country of Vehicle Registration						Powered Vehicles',
                ]
            ],

            'Sample spreadsheet - variation #5 (DFDS:2/AprMayJun col A+BN)' => [$this->getSampleSpreadsheetFive(), $this->zeroesApartFrom([
                    'AT' => 4, 'BE' => 3, 'BA' => 1, 'BG' => 2, 'CZ' => 4, 'DK' => 3, 'IE' => 39, 'EE' => 9, 'DE' => 6,
                    'HU' => 3, 'LT' => 7, 'NL' => 16, 'NO' => 2, 'PL' => 33, 'PT' => 1, 'RO' => 54, 'SK' => 2, 'ES' => 6,
                    'TR' => 44, 'GB' => 48, 'UA' => 5,
                    VehicleCount::OTHER_CODE_UNACCOMPANIED_TRAILERS => 8984,
                ]),
                292,
                8995,
                [],
                [
                    // On the entry row between "Unknown" and "Total Powered vehicles", we have a load of whitespace followed by a 0:
                    '																																																																	0',
                ]
            ],

            'Unused rows - variation #1' => [
                'Banana 123 Latvia 234',
                $this->zeroesApartFrom(['LV' => 234]),
                null,
                null,
                [
                    [
                        'countryName' => 'Banana',
                        'countryCode' => 'empty',
                        'count' => '123',
                    ]
                ]
            ],

            'Unused rows - variation #2' => [
                'ZZ 123 Latvia 234',
                $this->zeroesApartFrom(['LV' => 234]),
                null,
                null,
                [
                    [
                        'countryName' => '',
                        'countryCode' => 'ZZ',
                        'count' => '123',
                    ]
                ]
            ],

            'Unparsable rows' => [
                "One Two Three Four\nLatvia 234\nFive Six Seven Eight",
                $this->zeroesApartFrom(['LV' => 234]),
                null,
                null,
                [],
                [
                    'One Two Three Four',
                    'Five Six Seven Eight',
                ]
            ],

            // This weird row is actually just skipped/ignored
            'Skipped zero row' => [
                "											0\nLatvia 345",
                $this->zeroesApartFrom(['LV' => 345]),
                null,
                null,
                [],
                ['											0'],
            ],
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
    public function testMapper(
        string $input,
        array $expectedCounts,
        ?int $expectedTotalPoweredVehicles = null,
        ?int $expectedTotalVehicles = null,
        ?array $expectedUnusedRows = [],
        ?array $expectedUnparsableRows = null,
    ): void {
        $survey = $this->getSurvey();
        $form = $this->submitForm($survey, $input);

        $this->assertTrue($form->isSynchronized(), 'Input data cannot be parsed');

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

        $debugInfo = $data->getDataEntryDebugInfo();

        if ($expectedTotalPoweredVehicles !== null) {
            $this->assertEquals($expectedTotalPoweredVehicles, $debugInfo->getTotalPoweredVehicles(), 'Total powered vehicles mismatch');
        }

        if ($expectedTotalVehicles !== null) {
            $this->assertEquals($expectedTotalVehicles, $debugInfo->getTotalVehicles(), 'Total vehicles mismatch');
        }

        if ($expectedUnusedRows !== null) {
            $this->assertEquals($expectedUnusedRows, $debugInfo->getUnusedRows(), 'Unused rows mismatch');
        }

        if ($expectedUnparsableRows !== null) {
            $this->assertEquals($expectedUnparsableRows, $debugInfo->getUnparsableRows(), 'Unparsable rows mismatch');
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
        $this->assertNotNull($debug);

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

    protected function getSampleSpreadsheetOne(): string
    {
        return <<<EOS
Country of Vehicle Registration						Powered Vehicles						Country of Vehicle Registration						Powered Vehicles
																		
																		
   Albania (AL)						1						   Luxembourg (LU)						14
   Austria (AT)						29						   FYR of Macedonia (MK)						162
   Belarus (BY)						515						   Malta (MT)						2
   Belgium (BE)						159						   Moldova (MD)						10
   Bosnia (BA)						80						   Montenegro (ME)						3
   Bulgaria (BG)						2607						   Netherlands (NL)						98
   Croatia (HR)						89						   Norway (NO)						1
   Cyprus (CY)						4						   Poland (PL)						9350
   Czech Republic (CZ)						356						   Portugal (PT)						1251
   Denmark (DK)						4						   Romania (RO)						7432
   Eire (IE)						109						   Russia (RU)						35
   Estonia (EE)						18						   Serbia (RS)		Serbia				113
   Finland (FI)						1						   Slovakia (SK)						167
   France (FR)						314						   Slovenia (SI)						125
   Georgia (GE)						4						   Spain (ES)						2254
   Germany (DE)						264						   Sweden (SE)						30
   Greece (GR)						46						   Switzerland (CH)						8
   Hungary (HU)						2191						   Turkey (TR)						227
   Iceland (IS)						1						   Ukraine (UA)						1306
   Italy (IT)						199						   United Kingdom (GB)						1308
   Kosovo		Kosovo				0						   Other Countries						5457
   Latvia (LV)						379						   Unknown						209
   Lithuania (LT)						3229						Total Powered Vehicles						40161
																		
Please tick this box if the country breakdown is only estimated:												Unaccompanied Trailers						3
																		
												Total Vehicles						40164
EOS;
    }

    protected function getSampleSpreadsheetTwo(): string
    {
        return <<<EOS
Month 3	40164
Country	Movements
   Spain (ES)	2254
   Bulgaria (BG)	2607
   Poland (PL)	9350
   Unknown	209
   Netherlands (NL)	98
   Eire (IE)	109
   Lithuania (LT)	3229
   United Kingdom (GB)	1308
   Romania (RO)	7432
   Portugal (PT)	1251
   Hungary (HU)	2191
   Estonia (EE)	18
   Czech Republic (CZ)	356
   Germany (DE)	264
   Latvia (LV)	379
   Other Countries	5457
   Slovenia (SI)	125
   France (FR)	314
   Belgium (BE)	159
   Ukraine (UA)	1306
   Turkey (TR)	227
   Montenegro (ME)	3
   Italy (IT)	199
   Bosnia (BA)	80
   Greece (GR)	46
   Switzerland (CH)	8
   Denmark (DK)	4
   Croatia (HR)	89
   Slovakia (SK)	167
   FYR of Macedonia (MK)	162
   Belarus (BY)	515
   Serbia (RS)	113
   Austria (AT)	29
   Georgia (GE)	4
   Luxembourg (LU)	14
   Moldova (MD)	10
   Russia (RU)	35
   Sweden (SE)	30
   Norway (NO)	1
   Malta (MT)	2
   Iceland (IS)	1
   Albania (AL)	1
   Cyprus (CY)	4
   Finland (FI)	1
	
	
Unaccompanied Trailers	3
EOS;
    }

    protected function getSampleSpreadsheetThree(): string
    {
        return <<<EOS
Unaccompanied	127
AT	911
BE	3,613
BG	48
CH	5
CZ	652
DE	3,869
DK	370
EE	37
ES	6,526
FI	1
FR	3,345
GB	8,273
GR	77
HU	330
IE	397
IT	987
LT	1,032
LU	467
LV	139
MA	26
ME	15
MK	199
MT	8
NL	7,582
PL	2,148
PT	609
RO	1,028
SE	25
SI	60
SK	625
TR	85
ZA	10
Overall Result	43,499
EOS;
    }

    public function getSampleSpreadsheetFour(): string
    {
        return <<<EOS
Country of Vehicle Registration						Powered Vehicles							Country of Vehicle Registration						Powered Vehicles
																			
																			
   Albania (AL)						0						LU	   Luxembourg (LU)						467
   Austria (AT)						911						MK	   FYR of Macedonia (MK)						199
   Belarus (BY)						0						MT	   Malta (MT)						8
   Belgium (BE)						3613						MD	   Moldova (MD)						0
   Bosnia (BA)						0						ME	   Montenegro (ME)						15
   Bulgaria (BG)						48						NL	   Netherlands (NL)						7582
   Croatia (HR)						0						NO	   Norway (NO)						0
   Cyprus (CY)						0						PL	   Poland (PL)						2148
   Czech Republic (CZ)						652						PT	   Portugal (PT)						609
   Denmark (DK)						370						RO	   Romania (RO)						1028
   Eire (IE)						397						U	   Russia (RU)						0
   Estonia (EE)						37						RS	   Serbia (RS)		Serbia				0
   Finland (FI)						1						SK	   Slovakia (SK)						625
   France (FR)						3345						SI	   Slovenia (SI)						60
   Georgia (GE)						0						ES	   Spain (ES)						6526
   Germany (DE)						3869						SE	   Sweden (SE)						25
   Greece (GR)						77						CH	   Switzerland (CH)						5
   Hungary (HU)						330						TR	   Turkey (TR)						85
   Iceland (IS)						0						UA	   Ukraine (UA)						0
   Italy (IT)						987						GB	   United Kingdom (GB)						8273
   Kosovo		Kosovo				0							   Other Countries						36
   Latvia (LV)						139							   Unknown						
   Lithuania (LT)						1032						Overall Result	Total Powered Vehicles						43499
																			
Please tick this box if the country breakdown is only estimated:													Unaccompanied Trailers						127
																			
													Total Vehicles						43626
EOS;
    }

    public function getSampleSpreadsheetFive(): string
    {
        // N.B. The numbers are a long way to the right for some reason (lots of whitespace?!)
        //
        //      Additionally, these figures don't add up correctly, but that falls outside of the purview of this test,
        //      and is a problem with the source spreadsheet
        return <<<EOS
     Albania																																																																	0
     Austria																																																																	4
     Belarus																																																																	0
     Belgium																																																																	3
     Bosnia																																																																	1
     Bulgaria																																																																	2
     Croatia																																																																	0
     Cyprus																																																																	0
     Czech Republic																																																																	4
     Denmark																																																																	3
     Eire																																																																	39
     Estonia																																																																	9
     Finland																																																																	0
     France																																																																	0
     Georgia																																																																	0
     Germany																																																																	6
     Greece																																																																	0
     Hungary																																																																	3
     Iceland																																																																	0
     Italy																																																																	0
     Kosovo																																																																	0
     Latvia																																																																	0
     Lithuania																																																																	7
     Luxembourg																																																																	0
     FYR of Macedonia																																																																	0
     Malta																																																																	0
     Moldova																																																																	0
     Montenegro																																																																	0
     Netherlands																																																																	16
     Norway																																																																	2
     Poland																																																																	33
     Portugal																																																																	1
     Romania																																																																	54
     Russia																																																																	0
     Serbia																																																																	0
     Slovakia																																																																	2
     Slovenia																																																																	0
     Spain																																																																	6
     Sweden																																																																	0
     Switzerland																																																																	0
     Turkey																																																																	44
     United Kingdom																																																																	48
     Ukraine																																																																	5
     Other Countries																																																																	0
     Unknown																																																																	0
																																																																	0
Total Powered vehicles																																																																	292
Unacc. trailers																																																																	8984
TOTAL - VEHICLES																																																																	8995
EOS;
    }
}