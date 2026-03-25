<?php

namespace App\Tests\Unit\Serializer\Encoder;

use App\Serializer\Encoder\SqlServerInsertEncoder;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class SqlServerInsertEncoderTest extends TestCase
{
    private SqlServerInsertEncoder $encoder;

    protected function setUp(): void
    {
        $this->encoder = new SqlServerInsertEncoder();

        $loader = new FilesystemLoader(__DIR__ . '/../../../../templates');
        $twig = new Environment($loader);
        $this->encoder->setTwig($twig);
    }

    public function testSupportsEncoding(): void
    {
        $this->assertTrue($this->encoder->supportsEncoding('sql-server-insert'));
        $this->assertFalse($this->encoder->supportsEncoding('json'));
        $this->assertFalse($this->encoder->supportsEncoding('csv'));
    }

    public function testEncodeEmptyData(): void
    {
        $this->assertSame('', $this->encoder->encode([], 'sql-server-insert'));
    }

    public function testEncodeWithMixedData(): void
    {
        $data = [
            [
                'Name' => 'Acme Ltd',
                'Count' => 42,
                'Notes' => null,
                'Code' => '',
                'Description' => "Line one\nLine two",
                'Quote' => "It's a test",
            ],
        ];

        $result = $this->encoder->encode($data, 'sql-server-insert', [
            SqlServerInsertEncoder::TABLE_NAME_KEY => 'test_table',
            SqlServerInsertEncoder::FORCE_STRING_FIELDS => ['Count'],
        ]);

        $this->assertStringContainsString('INSERT INTO "test_table"', $result);
        $this->assertStringContainsString('"Name", "Count", "Notes", "Code", "Description", "Quote"', $result);
        $this->assertStringContainsString("'Acme Ltd'", $result);
        $this->assertStringContainsString("'42'", $result, 'Force-string field should be quoted');
        $this->assertStringContainsString('NULL', $result);
        $this->assertStringContainsString("''", $result);
        $this->assertStringContainsString("It''s a test", $result, 'Single quotes should be escaped');
        $this->assertStringContainsString("'+@lf+'", $result, 'Newlines should use SQL variable');
    }
}
