<?php

namespace App\Tests\Utility\International;

use App\Entity\International\Survey;
use App\Utility\International\SampleImporter;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SampleImporterTest extends WebTestCase
{
    private ?SampleImporter $sampleImporter;

    private string $tempFilename;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->sampleImporter = static::getContainer()->get(SampleImporter::class);
        $this->tempFilename = tempnam('/tmp', 'rfs-sample-import-test');
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->tempFilename) {
            unlink($this->tempFilename);
        }
    }

    public function validSurveyFixtureProvider(): array
    {
        $startDate = new \DateTime();
        $startDate->modify('+7 days');
        $endDate = new \DateTime();
        $endDate->modify('+13 days');
        return [
            ['"3770","1537","' . $startDate->format('Y-m-d') . '","' . $endDate->format('Y-m-d') . '","Test Company (Diss) LTD.","Building Name","The Street","An Industrial Estate","","","NR31 0NN"'],
        ];
    }

    /**
     * @dataProvider validSurveyFixtureProvider
     */
    public function testValidSurveys($csvLine)
    {
        file_put_contents($this->tempFilename, $csvLine);

        $data = $this->sampleImporter->getDataFromFilename($this->tempFilename);
        $result = $this->sampleImporter->processData($data['valid']);

        self::assertCount(1, $result['surveys']);
        self::assertCount(0, $result['invalidSurveys']);
        self::assertCount(0, $result['invalidData']);
        self::assertInstanceOf(Survey::class, $result['surveys'][0]);
    }


    public function invalidLineFixtureProvider(): array
    {
        return [
            ['"1537","2021-03-07","2021-03-13","Test Company (Diss) LTD.","Building Name","The Street","An Industrial Estate","","","NR31 0NN"'],
            ['"3770","1537,"2021-03-07","2021-03-13","Test Company (Diss) LTD.","Building Name","The Street","An Industrial Estate","","","NR31 0NN"'],
        ];
    }

    /**
     * @dataProvider invalidLineFixtureProvider
     */
    public function testInvalidFileData($csvLine)
    {
        file_put_contents($this->tempFilename, $csvLine);
        $data = $this->sampleImporter->getDataFromFilename($this->tempFilename);

        self::assertCount(1, $data['invalid']);
        self::assertCount(0, $data['valid']);
        self::assertSame($data['invalid'][0], $csvLine);
    }




    public function invalidArrayDataFixtureProvider(): array
    {
        return [
            ['"3770","1537",not-a-date,"2021-03-13","Test Company (Diss) LTD.","Building Name","The Street","An Industrial Estate","","","NR31 0NN"'],
            ['"3770","1537","2021-03-07",not-a-date,"Test Company (Diss) LTD.","Building Name","The Street","An Industrial Estate","","","NR31 0NN"'],
        ];
    }

    /**
     * @dataProvider invalidArrayDataFixtureProvider
     */
    public function testInvalidArrayData($csvLine)
    {
        file_put_contents($this->tempFilename, $csvLine);
        $data = $this->sampleImporter->getDataFromFilename($this->tempFilename);
        $result = $this->sampleImporter->processData($data['valid']);

        self::assertCount(0, $result['surveys']);
        self::assertCount(0, $result['invalidSurveys']);
        self::assertCount(1, $result['invalidData']);
    }


    public function invalidSurveyFixtureProvider(): array
    {
        $validStartDate = new \DateTime();
        $validStartDate->modify('+7 days');
        $validEndDate = new \DateTime();
        $validEndDate->modify('+13 days');

        $invalidStartDate = new \DateTime();
        $invalidStartDate->modify('-14 days');
        $invalidEndDate = new \DateTime();
        $invalidEndDate->modify('-8 days');

        return [
            ['"3770","1537","' . $validStartDate->format('Y-m-d') .'","' . $validEndDate->format('Y-m-d') . '","Test Company (Diss) LTD.","Building Name","The Street","An Industrial Estate","","","postcode"'],
            ['"3770","1537","' . $validStartDate->format('Y-m-d') .'","' . $validEndDate->format('Y-m-d') . '","Test Company (Diss) LTD.","","","","","","NR31 0NN"'],
            ['"3770","1537","' . $invalidStartDate->format('Y-m-d') . '","' . $invalidEndDate->format('Y-m-d') . '","Test Company (Diss) LTD.","Building Name","The Street","An Industrial Estate","","","NR31 0NN"'],
        ];
    }

    /**
     * @dataProvider invalidSurveyFixtureProvider
     */
    public function testInvalidSurvey($csvLine)
    {
        file_put_contents($this->tempFilename, $csvLine);
        $data = $this->sampleImporter->getDataFromFilename($this->tempFilename);
        $result = $this->sampleImporter->processData($data['valid']);

        self::assertCount(0, $result['surveys']);
        self::assertCount(1, $result['invalidSurveys']);
        self::assertCount(0, $result['invalidData']);
        self::assertInstanceOf(Survey::class, $result['invalidSurveys'][0]['survey']);
    }
}
