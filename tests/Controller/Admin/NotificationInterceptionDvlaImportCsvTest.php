<?php

declare(strict_types=1);

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\NotificationInterceptionDvlaImportController;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Serializer\Encoder\CsvEncoder;

/**
 * The CSV and XLSX upload paths must produce identically-normalised import data:
 * header row excluded, blank rows skipped, names trimmed, emails trimmed + lowercased.
 * The parity test feeds the same logical rows through both file formats and requires
 * byte-identical results, so the two implementations cannot drift apart.
 */
class NotificationInterceptionDvlaImportCsvTest extends TestCase
{
    private const string BOM = "\u{FEFF}";
    private const array HEADER = ['Operator Name', 'Email address'];

    /** @var array<string> */
    private array $tempFiles = [];

    #[\Override]
    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }
        $this->tempFiles = [];
    }

    private function createTestableController(): object
    {
        return new class(
            new RequestStack(),
            $this->createMock(EntityManagerInterface::class),
            'test',
            new CsvEncoder(),
            $this->createMock(LockFactory::class),
        ) extends NotificationInterceptionDvlaImportController {
            public function preProcessCsv(string $filename): array
            {
                $this->preProcessUploadedDvlaSpreadsheetFile($filename);
                return $this->dvlaImportData;
            }

            public function preProcessXlsx(string $filename): array
            {
                $this->preProcessUploadedDvlaSpreadsheetFilePhpOffice($filename);
                return $this->dvlaImportData;
            }

            public function deduplicate(array $importData): array
            {
                $this->dvlaImportData = $importData;
                $this->deduplicateImportData();
                return $this->dvlaImportData;
            }
        };
    }

    private function tempFile(string $suffix): string
    {
        $path = tempnam(sys_get_temp_dir(), 'dvla-import-test-') . $suffix;
        $this->tempFiles[] = $path;
        return $path;
    }

    /**
     * @param array<array{0: string, 1: string}> $rows
     */
    private function writeCsvRows(array $rows, array $header = self::HEADER): string
    {
        $lines = array_map(fn(array $row) => implode(',', $row), [$header, ...$rows]);
        $path = $this->tempFile('.csv');
        file_put_contents($path, self::BOM . implode("\r\n", $lines) . "\r\n");
        return $path;
    }

    /**
     * @param array<array{0: string, 1: string}> $rows
     */
    private function writeXlsxRows(array $rows, array $header = self::HEADER): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([$header, ...$rows]);

        $path = $this->tempFile('.xlsx');
        (new XlsxWriter($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        return $path;
    }

    /**
     * Rows exercising every normalisation rule, in both file formats.
     *
     * @return array<array{0: string, 1: string}>
     */
    private static function awkwardRows(): array
    {
        return [
            ['RJM CONSTRUCTION & EARTHWORKS LIMITED', 'Rob.maggs@rjmconstruction.co.uk'],
            [' Zeus Packaging (UK) Ltd ', ' richard.sullivan@zeuspackaging.com '],
            ['', ''],
            ['Kelda  Transport Management Ltd', 'DAVID.HIBBS@YORKSHIREWATER.CO.UK'],
            ["VLD65\u{00A0}TRANS UK LTD", 'ops@vld65.example'],
        ];
    }

    /**
     * @return array<array{0: string, 1: string}>
     */
    private static function expectedNormalisedRows(): array
    {
        return [
            ['RJM CONSTRUCTION & EARTHWORKS LIMITED', 'rob.maggs@rjmconstruction.co.uk'],
            ['Zeus Packaging (UK) Ltd', 'richard.sullivan@zeuspackaging.com'],
            // blank row skipped; internal whitespace (incl. NBSP) is collapsed to match
            // NotificationInterceptionCompanyName::setName() — otherwise whitespace variants
            // miss the existing-record lookup and violate the unique index on insert
            ['Kelda Transport Management Ltd', 'david.hibbs@yorkshirewater.co.uk'],
            ['VLD65 TRANS UK LTD', 'ops@vld65.example'],
        ];
    }

    public function testCsvAndXlsxProduceIdenticalImportData(): void
    {
        $csvData = $this->createTestableController()->preProcessCsv($this->writeCsvRows(self::awkwardRows()));
        $xlsxData = $this->createTestableController()->preProcessXlsx($this->writeXlsxRows(self::awkwardRows()));

        $this->assertSame($csvData, $xlsxData);
        $this->assertSame(self::expectedNormalisedRows(), $csvData);
    }

    public function testCsvWithoutBomIsAccepted(): void
    {
        $path = $this->tempFile('.csv');
        file_put_contents($path, "Operator Name,Email address\nAcme Ltd,fleet@acme.example\n");

        $data = $this->createTestableController()->preProcessCsv($path);

        $this->assertSame([['Acme Ltd', 'fleet@acme.example']], $data);
    }

    public function testWhitespaceVariantsOfSameNameDeduplicateToOneRecord(): void
    {
        // Regression: 'VLD65  TRANS UK LTD' / 'VLD65 TRANS UK LTD' survived dedup as two
        // records, then setName() collapsed both to the same value → unique index violation
        $controller = $this->createTestableController();
        $preProcessed = $controller->preProcessCsv($this->writeCsvRows([
            ['VLD65  TRANS UK LTD', 'a@vld65.example'],
            ['VLD65 TRANS UK LTD', 'b@vld65.example'],
        ]));

        $this->assertSame(
            [['VLD65 TRANS UK LTD', 'a@vld65.example,b@vld65.example']],
            $controller->deduplicate($preProcessed)
        );
    }

    public function testNamesWithLineBreaksAreCollapsed(): void
    {
        // A quoted CSV field can contain a line break; it must be collapsed like any
        // other whitespace rather than stored (which would create a jammed record)
        $path = $this->tempFile('.csv');
        file_put_contents($path, "Operator Name,Email address\n\"Acme\nHaulage Ltd\",ops@acme.example\n");

        $data = $this->createTestableController()->preProcessCsv($path);

        $this->assertSame([['Acme Haulage Ltd', 'ops@acme.example']], $data);
    }

    public function testWindows1252CsvIsAcceptedAndConvertedToUtf8(): void
    {
        // Some DVLA exports are Windows-1252 ("Non-ISO extended-ASCII"): no BOM, and
        // accented characters encoded as single high bytes that are invalid UTF-8
        $path = $this->tempFile('.csv');
        file_put_contents($path, "Operator Name,Email address\nCAF\xC9 EXPRESS LTD,fleet@cafe.example\n");

        $data = $this->createTestableController()->preProcessCsv($path);

        $this->assertSame([['CAFÉ EXPRESS LTD', 'fleet@cafe.example']], $data);
    }

    public function testCsvWithUnexpectedHeaderIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Column titles not as expected');

        $this->createTestableController()->preProcessCsv(
            $this->writeCsvRows([['Acme Ltd', 'fleet@acme.example']], header: ['Company', 'Contact'])
        );
    }

    public function testXlsxWithUnexpectedHeaderIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Column titles not as expected');

        $this->createTestableController()->preProcessXlsx(
            $this->writeXlsxRows([['Acme Ltd', 'fleet@acme.example']], header: ['Company', 'Contact'])
        );
    }
}
