<?php

namespace App\Tests\Utility;

use App\Utility\LoginLinkToken;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LoginLinkTokenTest extends TestCase
{
    public function testKnownEncodings(): void
    {
        self::assertSame('0pnfq', LoginLinkToken::encode('12345678'));
        self::assertSame('00000', LoginLinkToken::encode('00000000'));
        self::assertSame('00010', LoginLinkToken::encode('00000062'));
    }

    #[DataProvider('codes')]
    public function testRoundTrip(string $code): void
    {
        $token = LoginLinkToken::encode($code);
        self::assertMatchesRegularExpression('/^[0-9A-Za-z]{5}$/', $token);
        self::assertSame($code, LoginLinkToken::decode($token));
    }

    public static function codes(): iterable
    {
        foreach (['00000001', '00000062', '12345678', '99999999'] as $code) {
            yield $code => [$code];
        }
        mt_srand(20260930);
        for ($i = 0; $i < 20; $i++) {
            $code = str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
            yield "random {$code}" => [$code];
        }
    }

    #[DataProvider('invalidTokens')]
    public function testInvalidTokensDecodeToNull(string $token): void
    {
        self::assertNull(LoginLinkToken::decode($token));
    }

    public static function invalidTokens(): iterable
    {
        yield 'empty' => [''];
        yield 'too short' => ['abcd'];
        yield 'too long' => ['abcdef'];
        yield 'dash' => ['ab-de'];
        yield 'underscore' => ['ab_de'];
        yield 'value too large' => ['zzzzz'];
        yield 'first value too large' => ['6laZE']; // 10⁸
    }

    public function testLargestValidToken(): void
    {
        self::assertSame('99999999', LoginLinkToken::decode('6laZD'));
    }

    #[DataProvider('invalidCodes')]
    public function testEncodeRejectsAnythingButEightDigits(string $code): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LoginLinkToken::encode($code);
    }

    public static function invalidCodes(): iterable
    {
        yield 'seven digits' => ['1234567'];
        yield 'formatted' => ['1234-5678'];
        yield 'nine digits' => ['123456789'];
        yield 'letters' => ['test'];
    }
}
