<?php

declare(strict_types=1);

namespace App\Tests\Utility\OutwardCodes;

use App\Utility\OutwardCodes\OnspdReader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class OnspdReaderTest extends TestCase
{
    private const string FULL_CSV = <<<CSV
    pcd,pcd2,pcds,dointr,doterm,oscty
    "AB1 0AA","AB1  0AA","AB1 0AA",198001,199606,S99999999
    "AB1 0AB","AB1  0AB","AB1 0AB",198001,199606,S99999999
    "AB10 1AA","AB10 1AA","AB10 1AA",199606,,S99999999
    "AB10 1AB","AB10 1AB","AB10 1AB",199606,201001,S99999999
    "BN1  1AA","BN1  1AA","BN1 1AA",198001,,E10000011

    "SW1A1AA","SW1A 1AA","SW1A 1AA",198001,,E09000033
    CSV;

    private string $dir;
    private Filesystem $fs;

    protected function setUp(): void
    {
        $this->fs = new Filesystem();
        $this->dir = sys_get_temp_dir() . '/onspd-reader-test-' . uniqid();
        $this->fs->mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->dir);
    }

    public function testReadsPlainCsv(): void
    {
        $path = "{$this->dir}/ONSPD_TEST_UK.csv";
        file_put_contents($path, self::FULL_CSV);

        $counts = (new OnspdReader())->read($path);

        $this->assertSame([
            'AB1' => ['live' => 0, 'terminated' => 2],
            'AB10' => ['live' => 1, 'terminated' => 1],
            'BN1' => ['live' => 1, 'terminated' => 0],
            'SW1A' => ['live' => 1, 'terminated' => 0],
        ], $counts->counts);
        $this->assertSame(['ONSPD_TEST_UK.csv'], $counts->sources);
        $this->assertSame(['AB10', 'BN1', 'SW1A'], $counts->liveOutwardCodes());
        $this->assertSame(['AB1'], $counts->terminatedOutwardCodes());
        $this->assertSame(['AB1', 'AB10', 'BN1', 'SW1A'], $counts->allOutwardCodes());
    }

    public function testHandlesUtf8BomAndMixedCaseHeaders(): void
    {
        $path = "{$this->dir}/onspd.csv";
        file_put_contents($path, "\xEF\xBB\xBFPCD,PCDS,DOTERM\n\"BN1 1AA\",\"BN1 1AA\",\n");

        $counts = (new OnspdReader())->read($path);

        $this->assertSame(['BN1' => ['live' => 1, 'terminated' => 0]], $counts->counts);
    }

    public function testPrefersTheFullUkFileOverMultiCsvSplitsAndIgnoresOtherCsvs(): void
    {
        $zipPath = $this->makeZip([
            // Decoy: a lookup CSV with unrelated columns
            'Documents/Country names and codes UK as at 08_12.csv' => "CTRY12CD,CTRY12NM\nE92000001,England\n",
            // Split copies containing a code that must NOT be counted twice
            'Data/multi_csv/ONSPD_TEST_UK_AB.csv' => "pcd,pcds,doterm\n\"AB10 1AA\",\"AB10 1AA\",\n",
            'Data/multi_csv/ONSPD_TEST_UK_BN.csv' => "pcd,pcds,doterm\n\"BN1 1AA\",\"BN1 1AA\",\n",
            'Data/ONSPD_TEST_UK.csv' => self::FULL_CSV,
        ]);

        $sources = [];
        $counts = (new OnspdReader())->read($zipPath, function (string $s) use (&$sources) { $sources[] = $s; });

        $this->assertSame(['Data/ONSPD_TEST_UK.csv'], $sources);
        $this->assertSame(['ONSPD_TEST_UK.csv'], $counts->sources);
        $this->assertSame(['live' => 1, 'terminated' => 1], $counts->counts['AB10']);
    }

    public function testSumsMultiCsvSplitsWhenThereIsNoFullUkFile(): void
    {
        $zipPath = $this->makeZip([
            'Data/multi_csv/ONSPD_TEST_UK_BN.csv' => "pcd,pcds,doterm\n\"BN1 1AA\",\"BN1 1AA\",\n",
            'Data/multi_csv/ONSPD_TEST_UK_AB.csv' => "pcd,pcds,doterm\n\"AB10 1AA\",\"AB10 1AA\",\n\"AB10 1AB\",\"AB10 1AB\",200001\n",
        ]);

        $counts = (new OnspdReader())->read($zipPath);

        $this->assertSame(['ONSPD_TEST_UK_AB.csv', 'ONSPD_TEST_UK_BN.csv'], $counts->sources);
        $this->assertSame([
            'AB10' => ['live' => 1, 'terminated' => 1],
            'BN1' => ['live' => 1, 'terminated' => 0],
        ], $counts->counts);
    }

    public function testZipWithoutAnOnspdCsvIsRejected(): void
    {
        $zipPath = $this->makeZip(['readme.txt' => 'hello', 'other.csv' => "a,b\n1,2\n"]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No CSV containing the "pcds" and "doterm" columns');
        (new OnspdReader())->read($zipPath);
    }

    public function testCsvWithoutTheExpectedColumnsIsRejected(): void
    {
        $path = "{$this->dir}/bad.csv";
        file_put_contents($path, "postcode,status\n\"BN1 1AA\",live\n");

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('bad.csv does not contain the expected "pcds" and "doterm" columns');
        (new OnspdReader())->read($path);
    }

    public function testMissingFileIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('File not found');
        (new OnspdReader())->read("{$this->dir}/nope.zip");
    }

    #[DataProvider('outwardCodeProvider')]
    public function testOutwardCode(string $postcode, string $expected): void
    {
        $this->assertSame($expected, OnspdReader::outwardCode($postcode));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function outwardCodeProvider(): array
    {
        return [
            'single space' => ['BN1 1AA', 'BN1'],
            'double space (pcd2 style)' => ['AB1  0AA', 'AB1'],
            'lowercase with padding' => ['  sw1a 1aa ', 'SW1A'],
            'no space, 4-char outward' => ['SW1A1AA', 'SW1A'],
            'no space, 2-char outward' => ['B11AA', 'B1'],
            'outward only' => ['BN1', 'BN1'],
            'empty' => ['', ''],
            'whitespace only' => ['   ', ''],
        ];
    }

    /**
     * @param array<string, string> $members name => contents
     */
    private function makeZip(array $members): string
    {
        $path = "{$this->dir}/" . uniqid('onspd_') . '.zip';
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE));
        foreach ($members as $name => $contents) {
            $zip->addFromString($name, $contents);
        }
        $zip->close();

        return $path;
    }
}
