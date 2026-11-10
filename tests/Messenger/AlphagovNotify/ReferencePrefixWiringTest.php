<?php

namespace App\Tests\Messenger\AlphagovNotify;

use App\Messenger\AlphagovNotify\LoginMessageHandler;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\MessageHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Both Notify handlers get APP_NOTIFY_REFERENCE_PREFIX, so every reference sent identifies its environment. The bundle's
 * MessageHandler isn't autowired from App\, so its argument is bound in config/services.yaml.
 */
class ReferencePrefixWiringTest extends KernelTestCase
{
    private const string VAR = 'APP_NOTIFY_REFERENCE_PREFIX';

    private mixed $previous = null;

    protected function setUp(): void
    {
        // %env()% is resolved when the service is built, so setting it before booting is enough
        $this->previous = $_SERVER[self::VAR] ?? null;
        $_SERVER[self::VAR] = $_ENV[self::VAR] = 'wiring-test';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->previous === null) {
            unset($_SERVER[self::VAR], $_ENV[self::VAR]);
        } else {
            $_SERVER[self::VAR] = $_ENV[self::VAR] = $this->previous;
        }
    }

    public function testHandlersReceiveThePrefix(): void
    {
        $container = static::getContainer();

        foreach ([MessageHandler::class, LoginMessageHandler::class] as $class) {
            $handler = $container->get($class);
            self::assertSame('wiring-test', (new \ReflectionProperty($handler, 'referencePrefix'))->getValue($handler), $class);
        }
    }
}
