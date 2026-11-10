<?php

namespace App\Tests\Messenger\AlphagovNotify;

use Alphagov\Notifications\Client;
use App\Messenger\AlphagovNotify\LoginMessageHandler;
use App\Messenger\AlphagovNotify\RoRoLoginEmail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * RoRo login emails don't go through the bundle's MessageHandler, so they need the environment's reference prefix
 * applied here (their reference is otherwise empty).
 */
class LoginMessageHandlerTest extends TestCase
{
    public static function prefixes(): array
    {
        return [
            // %env(default::VAR)% resolves to null for an unset or empty variable
            'unset' => [null, ''],
            'empty' => ['', ''],
            'dev' => ['dev', 'dev'],
        ];
    }

    #[DataProvider('prefixes')]
    public function testReferenceIsTheEnvironmentPrefix(?string $prefix, string $expectedReference): void
    {
        $sent = null;
        $client = $this->createStub(Client::class);
        $client->method('sendEmail')->willReturnCallback(function (...$args) use (&$sent) {
            $sent = $args;
            return ['id' => 'notify-id'];
        });

        $result = (new LoginMessageHandler($client, $prefix))(new RoRoLoginEmail('someone@example.com', ['login_link' => 'x']));

        self::assertSame(['id' => 'notify-id'], $result);
        self::assertSame('someone@example.com', $sent[0]);
        self::assertSame(['login_link' => 'x'], $sent[2]);
        self::assertSame($expectedReference, $sent[3] ?? '');
    }
}
