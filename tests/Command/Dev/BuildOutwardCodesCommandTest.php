<?php

declare(strict_types=1);

namespace App\Tests\Command\Dev;

use App\Command\Dev\BuildOutwardCodesCommand;
use App\Utility\OutwardCodes\OnspdReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BuildOutwardCodesCommandTest extends TestCase
{
    private const string CSV = <<<CSV
    pcd,pcds,doterm
    "AB1 0AA","AB1 0AA",199606
    "AB10 1AA","AB10 1AA",
    "AB11 1AA","AB11 1AA",
    "AB12 1AA","AB12 1AA",
    "AB99 1AA","AB99 1AA",
    "BN1 1AA","BN1 1AA",
    "SW1A 1AA","SW1A 1AA",
    "SW2 1AA","SW2 1AA",
    "SW3 1AA","SW3 1AA",
    "GIR 0AA","GIR 0AA",
    CSV;

    private string $projectDir;
    private Filesystem $fs;

    protected function setUp(): void
    {
        $this->fs = new Filesystem();
        $this->projectDir = sys_get_temp_dir() . '/build-outward-codes-test-' . uniqid();
        $this->fs->mkdir($this->projectDir);
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->projectDir);
    }

    private function makeTester(?HttpClientInterface $httpClient = null): CommandTester
    {
        $httpClient ??= new MockHttpClient(fn() => $this->fail('No HTTP request expected'));

        return new CommandTester(new BuildOutwardCodesCommand($httpClient, new OnspdReader(), $this->projectDir));
    }

    private function writeZip(string $name, string $csv): string
    {
        $path = "{$this->projectDir}/{$name}";
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE));
        $zip->addFromString('Data/ONSPD_JAN_2031_UK.csv', $csv);
        $zip->close();

        return $path;
    }

    public function testBuildsDataFileFromLocalZip(): void
    {
        $tester = $this->makeTester();
        $exit = $tester->execute(['--input' => $this->writeZip('ONSPD_TEST.zip', self::CSV)]);

        $this->assertSame(Command::SUCCESS, $exit, $tester->getDisplay());

        $outputPath = "{$this->projectDir}/" . BuildOutwardCodesCommand::DEFAULT_OUTPUT;
        $this->assertFileExists($outputPath);

        $contents = file_get_contents($outputPath);
        $this->assertStringContainsString("# Source: ONSPD_JAN_2031_UK.csv", $contents);
        $this->assertStringContainsString("# Contents: every outward code in the source, including those whose postcodes have all been terminated", $contents);
        $this->assertStringContainsString("#   Contains OS data \u{a9} Crown copyright and database right 2031", $contents);
        $this->assertStringContainsString("#   Contains Royal Mail data \u{a9} Royal Mail copyright and database right 2031", $contents);
        $this->assertStringContainsString("Northern Ireland End User Licence", $contents);
        $this->assertStringEndsWith("AB1,AB10-12,AB99\nBN1\nSW1A,SW2-3\n", $contents);
        $this->assertStringNotContainsString("\nGIR", $contents, 'GIR is deliberately excluded (see EXCLUDED_OUTWARD_CODES)');
        $this->assertStringContainsString('# Excluded on purpose (see BuildOutwardCodesCommand::EXCLUDED_OUTWARD_CODES): GIR', $contents);
        $this->assertStringContainsString('Excluding outward code(s) listed in EXCLUDED_OUTWARD_CODES: GIR', $tester->getDisplay());

        $display = preg_replace('/\s+/', ' ', $tester->getDisplay());
        $this->assertStringContainsString('9 outward codes (9 live, 1 terminated-only in source) as 6 entries across 3 postcode areas', $display);
    }

    public function testLiveOnlyAndCustomOutputPath(): void
    {
        $tester = $this->makeTester();
        $exit = $tester->execute([
            '--input' => $this->writeZip('ONSPD_TEST.zip', self::CSV),
            '--live-only' => true,
            '--output' => 'out/codes.txt',
        ]);

        $this->assertSame(Command::SUCCESS, $exit, $tester->getDisplay());

        $contents = file_get_contents("{$this->projectDir}/out/codes.txt");
        $this->assertStringContainsString('--live-only', $contents);
        $this->assertStringContainsString('# Contents: live outward codes only', $contents);
        $this->assertStringEndsWith("AB10-12,AB99\nBN1\nSW1A,SW2-3\n", $contents);
        $this->assertStringNotContainsString('AB1,', $contents);
    }

    public function testAcceptsAnExtractedCsv(): void
    {
        $csvPath = "{$this->projectDir}/ONSPD_TEST_UK.csv";
        file_put_contents($csvPath, self::CSV);

        $tester = $this->makeTester();
        $exit = $tester->execute(['--input' => $csvPath, '--output' => "{$this->projectDir}/abs.txt"]);

        $this->assertSame(Command::SUCCESS, $exit, $tester->getDisplay());
        $contents = file_get_contents("{$this->projectDir}/abs.txt");
        $this->assertStringEndsWith("AB1,AB10-12,AB99\nBN1\nSW1A,SW2-3\n", $contents);
        $this->assertStringContainsString('Crown copyright and database right ' . date('Y'), $contents);
    }

    public function testDownloadsWhenNoInputGiven(): void
    {
        $zipPath = $this->writeZip('download.zip', self::CSV);
        $zipBytes = file_get_contents($zipPath);
        $requestedUrls = [];

        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$requestedUrls, $zipBytes) {
            $requestedUrls[] = $url;
            return new MockResponse(str_split($zipBytes, 1024), [
                'http_code' => 200,
                'response_headers' => ['content-length' => (string) strlen($zipBytes)],
            ]);
        });

        $tempFilesBefore = glob(sys_get_temp_dir() . '/onspd_*') ?: [];

        $tester = $this->makeTester($httpClient);
        $exit = $tester->execute(['--url' => 'https://example.test/onspd.zip']);

        $this->assertSame(Command::SUCCESS, $exit, $tester->getDisplay());
        $this->assertSame(['https://example.test/onspd.zip'], $requestedUrls);
        $this->assertStringEndsWith("AB1,AB10-12,AB99\nBN1\nSW1A,SW2-3\n", file_get_contents("{$this->projectDir}/" . BuildOutwardCodesCommand::DEFAULT_OUTPUT));
        $this->assertSame($tempFilesBefore, glob(sys_get_temp_dir() . '/onspd_*') ?: [], 'temporary download should be removed');
    }

    public function testFailedDownloadReportsAndSuggestsInput(): void
    {
        $httpClient = new MockHttpClient(fn() => new MockResponse('Not found', ['http_code' => 404]));

        $tester = $this->makeTester($httpClient);
        $exit = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exit);
        $this->assertStringContainsString('HTTP 404', $tester->getDisplay());
        $this->assertStringContainsString('--input', $tester->getDisplay());
        $this->assertFileDoesNotExist("{$this->projectDir}/" . BuildOutwardCodesCommand::DEFAULT_OUTPUT);
    }

    public function testUnusableInputFails(): void
    {
        $badPath = "{$this->projectDir}/bad.csv";
        file_put_contents($badPath, "a,b\n1,2\n");

        $tester = $this->makeTester();
        $exit = $tester->execute(['--input' => $badPath]);

        $this->assertSame(Command::FAILURE, $exit);
        $this->assertStringContainsString('does not contain the expected "pcds" and "doterm" columns', $tester->getDisplay());
    }

    public function testOutwardCodeNotMatchingTheAppRegexFailsTheBuild(): void
    {
        // "ABC1" (three letters) is not a format the app's postcode regex accepts, and isn't in EXCLUDED_OUTWARD_CODES
        $csv = self::CSV . "\n\"ABC1 1AA\",\"ABC1 1AA\",\n";

        $tester = $this->makeTester();
        $exit = $tester->execute(['--input' => $this->writeZip('ONSPD_TEST.zip', $csv)]);

        $this->assertSame(Command::FAILURE, $exit);
        $display = preg_replace('/\s+/', ' ', $tester->getDisplay());
        $this->assertStringContainsString('do not match the app\'s postcode regex (PostcodeHelper::getRegex): ABC1', $display);
        $this->assertStringContainsString('EXCLUDED_OUTWARD_CODES', $display);
        $this->assertFileDoesNotExist("{$this->projectDir}/" . BuildOutwardCodesCommand::DEFAULT_OUTPUT);
    }

    public function testExcludedCodeDoesNotTripTheRegexCheck(): void
    {
        // GIR is in the fixture CSV and would fail the regex, but it is excluded first
        $tester = $this->makeTester();
        $exit = $tester->execute(['--input' => $this->writeZip('ONSPD_TEST.zip', self::CSV)]);

        $this->assertSame(Command::SUCCESS, $exit, $tester->getDisplay());
    }
}
