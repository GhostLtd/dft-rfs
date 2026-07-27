<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\NotificationInterceptionCompanyName;
use PHPUnit\Framework\TestCase;

class NotificationInterceptionCompanyNameTest extends TestCase
{
    /**
     * @dataProvider nameProvider
     */
    public function testSetNameNormalisesWhitespace(?string $input, ?string $expected): void
    {
        $companyName = (new NotificationInterceptionCompanyName())->setName($input);

        $this->assertSame($expected, $companyName->getName());
    }

    public function nameProvider(): array
    {
        return [
            'null' => [null, null],
            'plain' => ['Acme Ltd', 'Acme Ltd'],
            'trimmed' => ['  Acme Ltd  ', 'Acme Ltd'],
            'double space collapsed' => ['Acme  Ltd', 'Acme Ltd'],
            'run of spaces collapsed' => ['Acme    Haulage   Ltd', 'Acme Haulage Ltd'],
            'nbsp collapsed' => ["Acme\u{00A0}Ltd", 'Acme Ltd'],
            'nbsp run collapsed' => ["Acme \u{00A0} Ltd", 'Acme Ltd'],
            'tab collapsed' => ["Acme\tLtd", 'Acme Ltd'],
            // Line breaks are deliberately preserved: historical jammed records contain them, and
            // silently collapsing on load-and-save would corrupt them differently instead of
            // leaving them for the rfs:dev:lcni-fix-jammed-names command
            'newline preserved' => ["Acme Ltd\nAcme Limited", "Acme Ltd\nAcme Limited"],
        ];
    }
}
