<?php

namespace App\Tests\Utility;

use App\Utility\ReorderUtils;
use PHPUnit\Framework\TestCase;

class ReorderUtilsTest extends TestCase
{
    public function isMappingValidDataProvider(): array
    {
        return [
            'valid 3 items' => [[1, 2, 3], 3, true],
            'valid reordered' => [[3, 1, 2], 3, true],
            'out of bounds value' => [[1, 2, 3, 4], 3, false],  // THE BUG
            'too many mappings' => [[1, 2, 3, 4, 5], 3, false],
            'too few mappings' => [[1, 2], 3, false],
            'duplicate values' => [[1, 1, 2], 3, false],
            'missing value' => [[1, 3, 3], 3, false],
            'zero value' => [[0, 1, 2], 3, false],
            'empty mapping' => [[], 0, true],
            'single item' => [[1], 1, true],
        ];
    }

    /**
     * @dataProvider isMappingValidDataProvider
     */
    public function testIsMappingValid(array $mapping, int $numberOfItems, bool $expected): void
    {
        $this->assertEquals($expected, ReorderUtils::isMappingValid($mapping, $numberOfItems));
    }
}
