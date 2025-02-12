<?php

namespace App\Tests\NewFunctional\Wizard\Admin\Domestic;

use App\Tests\NewFunctional\Wizard\Admin\AbstractAdminUploadTest;
use App\Tests\NewFunctional\Wizard\Form\FormTestCase;
use Facebook\WebDriver\WebDriverBy;

class DvlaBulkImportTest extends AbstractAdminUploadTest
{
    public function dataInitialPage(): array
    {
        return [
            // Fails to auto-detect region / start-date
            [false, false, 'whatever.txt', ['import_dvla_file_upload[override_defaults]' => '0']],

            // Correct file name, but is empty
            [false, false, 'csrgt_output_surveyweek_1_20201119143500.txt', ['import_dvla_file_upload[override_defaults]' => '0']],

            // Best case scenario - context from filename
            [true, true,
                'csrgt_output_surveyweek_1_20201119143500.txt',
                [
                    'import_dvla_file_upload[override_defaults]' => '0',
                ],
            ],
            [true, true,
                'csrgt_output_ni_surveyweek_32_20221216123100.txt',
                [
                    'import_dvla_file_upload[override_defaults]' => '0',
                ],
            ],

            // Even if you (partially) match the filename format, (e.g. in this case we could potentially
            // infer the region), that's not good enough and you'll still need to fill the isNorthernIreland field
            [false, true,
                'csrgt_output_ni_surveyweek_whatever.txt',
                [
                    'import_dvla_file_upload[override_defaults]' => '1',
                    'import_dvla_file_upload[survey_options][surveyPeriodStart][day]' => '23',
                    'import_dvla_file_upload[survey_options][surveyPeriodStart][month]' => '5',
                    'import_dvla_file_upload[survey_options][surveyPeriodStart][year]' => '2021',
                ],
            ],

            // Ditto for mangled regions with seemingly fine dates...
            [false, true,
                'csrgt_output_what_surveyweek_1_20201119143500.txt',
                [
                    'import_dvla_file_upload[override_defaults]' => '1',
                    'import_dvla_file_upload[survey_options][isNorthernIreland]' => '1',
                ],
            ],

            // Success case - fill out all options...
            [true, true,
                'whatever.txt',
                [
                    'import_dvla_file_upload[override_defaults]' => '1',
                    'import_dvla_file_upload[survey_options][isNorthernIreland]' => '1',
                    'import_dvla_file_upload[survey_options][surveyPeriodStart][day]' => '23',
                    'import_dvla_file_upload[survey_options][surveyPeriodStart][month]' => '5',
                    'import_dvla_file_upload[survey_options][surveyPeriodStart][year]' => '2021',
                ],
            ],
        ];
    }


    /**
     * @dataProvider dataInitialPage
     */
    public function testInitialPage(bool $expectedToSucceed, bool $addContentsToFixtureFile, string $filename, array $otherFormData): void
    {
        $this->initialiseTest([]);
        $this->clickLinkContaining('DVLA bulk import');

        // The first form doesn't care what the contents of the form are, apart from that it is not an empty file
        $fixturePath = $this->createFixture($filename, $addContentsToFixtureFile ? 'Banana' : '');

        // Errors don't properly get attached to this form, so in case of
        // failure we need to check that we've NOT changed page

        $this->formTestAction('/csrgt/dvla-import', 'import_dvla_file_upload_submit', [
            new FormTestCase(array_merge([
                'import_dvla_file_upload[file]' => $fixturePath,
            ], $otherFormData), [], null, !$expectedToSucceed)
        ]);

        if (!$expectedToSucceed) {
            $this->pathTestAction('/csrgt/dvla-import');
        }
    }

    public function dataReviewPage(): array
    {
        // TODO: This could do with more tests...
        $nextWeek = (new \DateTime('next week'));
        $filename = "csrgt_output_surveyweek_{$nextWeek->format('W')}_{$nextWeek->format('Ymd')}143500.txt";

        return [
            [
                1,
                $filename,
                (
                    "    BROKENABCDE LTD 1                                        THE OLD CHURCH SCHOOL         FROME                                                                                     SOMERSET                      BA113HQ112C1 482011000199912062009\n".
                    "AU11AXZGHOST LTD 2                                       THE OLD CHURCH SCHOOL         FROME                                                                                     SOMERSET                      BA113HQ112C1 482011000199912062009\n"
                ),
                [],
            ],
            [
                2,
                $filename,
                (
                    "AU11AXYGHOST LTD 1                                       THE OLD CHURCH SCHOOL         FROME                                                                                     SOMERSET                      BA113HQ112C1 482011000199912062009\n".
                    "AU11AXZGHOST LTD 2                                       THE OLD CHURCH SCHOOL         FROME                                                                                     SOMERSET                      BA113HQ112C1 482011000199912062009\n"
                ),
                [],
            ]
        ];
    }

    /**
     * @dataProvider dataReviewPage
     */
    public function testReviewPage(int $expectedValidRecords, string $filename, string $fileData, array $otherFormData=[]): void
    {
        $this->initialiseTest([]);
        $this->doDvlaImport($expectedValidRecords, $filename, $fileData, $otherFormData);
    }


    public function testDoubleImport(): void
    {
        $nextWeek = (new \DateTime('next week'));
        $fileData = "AU11AXYGHOST LTD 1                                       THE OLD CHURCH SCHOOL         FROME                                                                                     SOMERSET                      BA113HQ112C1 482011000199912062009\n";
        $filename = "csrgt_output_surveyweek_{$nextWeek->format('W')}_{$nextWeek->format('Ymd')}143500.txt";

        $this->initialiseTest([]);

        // first import
        $this->doDvlaImport(1, $filename, $fileData);
        $this->formTestAction('/csrgt/dvla-import/review', 'import_dvla_review_data_submit', [
            new FormTestCase([]),
        ]);
        $this->pathTestAction('/csrgt/dvla-import/summary');

        // second import
        $this->doDvlaImport(0, $filename, $fileData);
        $text = $this->client->getCrawler()
            ->findElement(WebDriverBy::cssSelector('#main-content .govuk-list li'))
            ->getText();
        $this->assertStringContainsString('A survey with this start date and registration mark already exists', $text);
    }

    protected function doDvlaImport(int $expectedValidRecords, string $filename, string $fileData, array $otherFormData=[]): void
    {
        $this->clickLinkContaining('DVLA bulk import');

        $fixturePath = $this->createFixture($filename, $fileData);

        $this->formTestAction('/csrgt/dvla-import', 'import_dvla_file_upload_submit', [
            new FormTestCase(array_merge([
                'import_dvla_file_upload[file]' => $fixturePath,
                'import_dvla_file_upload[override_defaults]' => '0',
            ], $otherFormData))
        ]);

        $this->pathTestAction('/csrgt/dvla-import/review');

        $text = $this->client->getCrawler()
            ->findElement(WebDriverBy::id('import_dvla_review_data_review_data-hint'))
            ->getText();

        $this->assertStringContainsString("{$expectedValidRecords} records imported", $text);

    }
}
