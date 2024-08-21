<?php

namespace App\Tests\ML;

use App\ML\Config\DefaultTextConfig;
use PHPUnit\Framework\TestCase;

class DefaultTextConfigTest extends TestCase
{
    public function dataNormalizeCode(): array
    {
        return [
            ['10.1', '10.1'],
            ['1.A', '1.A'],
            ['010.1', '10.1'],
            ['01.A', '1.A'],
            ['20', '20.0'],
            ['18.5', '18.5'],
            ['   18.5    ', '18.5'],
        ];
    }

    /**
     * @dataProvider dataNormalizeCode
     */
    public function testNormalizeCode(string $input, string $expectedOutput): void
    {
        $config = new DefaultTextConfig();
        $output = $config->normalizeCode($input);

        $this->assertEquals($expectedOutput, $output);
    }
}