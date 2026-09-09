<?php

declare(strict_types=1);

namespace App\Tests\Utility\OutwardCodes;

use App\Utility\OutwardCodes\OutwardCodeChecker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OutwardCodeCheckerTest extends TestCase
{
    private const string DATA = <<<TXT
    # UK outward codes (comment line)
    # Format: one line per postcode area
    AL1-10
    BN1-3,BN5-6,BN99
    E1-3,E1W,E20

    SW1A,SW1E,SW2-4
    GIR
    TXT;

    /** @var list<string> */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
    }

    private function makeChecker(string $contents = self::DATA): OutwardCodeChecker
    {
        $path = tempnam(sys_get_temp_dir(), 'outward-codes-test-');
        $this->assertNotFalse($path);
        $this->files[] = $path;
        file_put_contents($path, $contents);

        return new OutwardCodeChecker($path);
    }

    #[DataProvider('outwardCodeProvider')]
    public function testIsValidOutwardCode(string $outwardCode, bool $expected): void
    {
        $this->assertSame($expected, $this->makeChecker()->isValidOutwardCode($outwardCode));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function outwardCodeProvider(): array
    {
        return [
            'start of range' => ['AL1', true],
            'middle of range' => ['AL5', true],
            'end of range' => ['AL10', true],
            'beyond range' => ['AL11', false],
            'gap between ranges' => ['BN4', false],
            'single code after ranges' => ['BN99', true],
            'gap before single' => ['BN7', false],
            'suffixed code' => ['E1W', true],
            'suffixed code not listed' => ['E1X', false],
            'numeric code alongside suffixed' => ['E1', true],
            'suffixed-only area, unsuffixed number' => ['SW1', false],
            'suffixed-only area, listed suffix' => ['SW1E', true],
            'range in suffixed area' => ['SW3', true],
            'lowercase' => ['bn2', true],
            'surrounding whitespace' => ['  BN2 ', true],
            'unknown area' => ['ZZ1', false],
            'number-only lookalike (not zero-padded)' => ['AL01', false],
            'empty' => ['', false],
        ];
    }

    #[DataProvider('postcodeProvider')]
    public function testIsValidPostcode(string $postcode, bool $expected): void
    {
        $this->assertSame($expected, $this->makeChecker()->isValidPostcode($postcode));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function postcodeProvider(): array
    {
        return [
            'full postcode with space' => ['BN1 1AA', true],
            'full postcode without space' => ['BN11AA', true],
            'full postcode lowercase, padded' => [' bn1 1aa ', true],
            'full postcode, 4-char outward' => ['SW1A 1AA', true],
            'full postcode, 4-char outward, no space' => ['SW1A1AA', true],
            'full postcode, unknown district' => ['BN4 1AA', false],
            'full postcode, unknown district, no space' => ['BN41AA', false],
            'outward code only' => ['BN1', true],
            'outward code only, unknown' => ['BN4', false],
            'suffixed outward code only' => ['E1W', true],
            'two-digit district, no space' => ['AL101AA', true],
            'single-letter area, no space' => ['E11AA', true],
            'GIR 0AA (no digit in outward code)' => ['GIR 0AA', true],
            'GIR0AA without space' => ['GIR0AA', true],
            'GIR outward only' => ['GIR', true],
            'unknown letter-only outward code' => ['ABC 1AA', false],
            'not a postcode shape' => ['hello', false],
            'inward code only' => ['1AA', false],
            'empty' => ['', false],
        ];
    }

    public function testCommentsBlankLinesAndWhitespaceInFileAreIgnored(): void
    {
        $checker = $this->makeChecker("# comment\n\n  BN1-2 , BN5 \n\n# trailing comment\n");

        $this->assertTrue($checker->isValidOutwardCode('BN1'));
        $this->assertTrue($checker->isValidOutwardCode('BN5'));
        $this->assertFalse($checker->isValidOutwardCode('BN3'));
    }

    public function testMissingDataFileIsReportedOnFirstUseNotConstruction(): void
    {
        $checker = new OutwardCodeChecker(sys_get_temp_dir() . '/does-not-exist-' . uniqid() . '.txt');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Outward code data file not found');
        $checker->isValidOutwardCode('BN1');
    }

    public function testMalformedRangeIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid range "BN5-2" on line 2');
        $this->makeChecker("# header\nBN5-2\n")->isValidOutwardCode('BN1');
    }

    public function testGarbledRangeIsRejected(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid range "BN1-X"');
        $this->makeChecker("BN1-X\n")->isValidOutwardCode('BN1');
    }

    public function testFileIsParsedOnlyOnce(): void
    {
        $checker = $this->makeChecker();
        $this->assertTrue($checker->isValidOutwardCode('BN1'));

        // Removing the file after first use must not affect subsequent checks
        unlink(array_pop($this->files));

        $this->assertTrue($checker->isValidOutwardCode('BN2'));
        $this->assertFalse($checker->isValidOutwardCode('BN4'));
    }

    /**
     * Smoke test against the committed data file, so a regenerated file that fails to parse is caught.
     */
    #[DataProvider('realDataProvider')]
    public function testRealDataFile(string $postcode, bool $expected): void
    {
        $checker = new OutwardCodeChecker(__DIR__ . '/../../../' . OutwardCodeChecker::DATA_FILE);

        $this->assertSame($expected, $checker->isValidPostcode($postcode));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function realDataProvider(): array
    {
        return [
            'Brighton' => ['BN1 1AA', true],
            'BN4 (terminated district) is included' => ['BN4 1AA', true],
            'BN19 has never existed' => ['BN19 1AA', false],
            'Buckingham Palace' => ['SW1A 1AA', true],
            'SW1 without suffix does not exist' => ['SW1 1AA', false],
            'Wapping' => ['E1W 1AA', true],
            'Belfast' => ['BT1 1AA', true],
            'Guernsey' => ['GY1 1AA', true],
            'Isle of Man' => ['IM1 1AA', true],
            'partial, Birmingham' => ['B1', true],
            'partial, terminated district (B22)' => ['B22', true],
            'partial, no such district (B39)' => ['B39', false],
            'made-up area' => ['ZZ1 1AA', false],
            'GIR 0AA excluded by the build command' => ['GIR 0AA', false],
        ];
    }
}
