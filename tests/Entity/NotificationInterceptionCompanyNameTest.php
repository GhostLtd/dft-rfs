<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\NotificationInterceptionCompanyName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NotificationInterceptionCompanyNameTest extends TestCase
{
    public static function nameProvider(): array
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
            // Line breaks collapse like any other whitespace: normaliseName() is the single
            // stored-name rule that lookups and the DVLA import build on. Historical jammed
            // rows are unaffected — they only exist as DB data, which hydrates via reflection
            // and reaches the rfs:dev:lcni-fix-jammed-names command without passing setName()
            'newline collapsed' => ["Acme Ltd\nAcme Limited", 'Acme Ltd Acme Limited'],
        ];
    }

    #[DataProvider('nameProvider')]
    public function testSetNameNormalisesWhitespace(?string $input, ?string $expected): void
    {
        $companyName = (new NotificationInterceptionCompanyName())->setName($input);

        $this->assertSame($expected, $companyName->getName());
    }
}
