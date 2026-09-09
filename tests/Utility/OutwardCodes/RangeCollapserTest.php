<?php

declare(strict_types=1);

namespace App\Tests\Utility\OutwardCodes;

use App\Utility\OutwardCodes\RangeCollapser;
use PHPUnit\Framework\TestCase;

class RangeCollapserTest extends TestCase
{
    public function testCollapsesConsecutiveDistrictsIntoRanges(): void
    {
        $result = RangeCollapser::collapse(['BN1', 'BN2', 'BN3', 'BN4', 'BN7', 'BN8', 'BN9']);

        $this->assertSame(['BN' => ['BN1-4', 'BN7-9']], $result);
    }

    public function testInputOrderAndDuplicatesDoNotMatter(): void
    {
        $result = RangeCollapser::collapse(['BN9', 'bn2', ' BN1 ', 'BN8', 'BN3', 'BN7', 'BN4', 'BN1']);

        $this->assertSame(['BN' => ['BN1-4', 'BN7-9']], $result);
    }

    public function testSingletonsAreNotWrittenAsRanges(): void
    {
        $result = RangeCollapser::collapse(['AB10', 'AB12', 'AB13', 'AB99']);

        $this->assertSame(['AB' => ['AB10', 'AB12-13', 'AB99']], $result);
    }

    public function testNumericOrderNotStringOrder(): void
    {
        $result = RangeCollapser::collapse(['B10', 'B9', 'B11', 'B1', 'B2']);

        $this->assertSame(['B' => ['B1-2', 'B9-11']], $result);
    }

    public function testSuffixedDistrictsAreListedIndividuallyWithoutBreakingRuns(): void
    {
        $result = RangeCollapser::collapse(['E1', 'E1W', 'E2', 'E3', 'E20', 'SW1A', 'SW1E', 'SW1P', 'SW3', 'SW4']);

        $this->assertSame([
            'E' => ['E1-3', 'E1W', 'E20'],
            'SW' => ['SW1A', 'SW1E', 'SW1P', 'SW3-4'],
        ], $result);
    }

    public function testAreasAreSortedAlphabeticallyAndAreasSharingAPrefixAreKeptApart(): void
    {
        $result = RangeCollapser::collapse(['SW1A', 'S1', 'S2', 'SA1', 'AB1', 'A1']);

        $this->assertSame(['A', 'AB', 'S', 'SA', 'SW'], array_keys($result));
        $this->assertSame(['S1-2'], $result['S']);
    }

    public function testNonStandardCodesAreEmittedVerbatimAfterTheirArea(): void
    {
        $result = RangeCollapser::collapse(['GIR', 'G1', 'G2']);

        $this->assertSame(['G' => ['G1-2'], 'GIR' => ['GIR']], $result);
    }

    public function testEmptyInput(): void
    {
        $this->assertSame([], RangeCollapser::collapse([]));
        $this->assertSame('', RangeCollapser::format([]));
    }

    public function testFormatWritesOneLinePerArea(): void
    {
        $formatted = RangeCollapser::format(RangeCollapser::collapse(['BN1', 'BN2', 'BN7', 'AL1', 'SW1A']));

        $this->assertSame("AL1\nBN1-2,BN7\nSW1A\n", $formatted);
    }
}
