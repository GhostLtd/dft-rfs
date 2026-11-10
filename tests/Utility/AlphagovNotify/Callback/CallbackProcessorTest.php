<?php

namespace App\Tests\Utility\AlphagovNotify\Callback;

use App\Entity\NotifyCallback;
use App\Utility\AlphagovNotify\Callback\CallbackOutcome;
use App\Utility\AlphagovNotify\Callback\CallbackProcessor;
use Doctrine\DBAL\Connection;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Clock\ClockInterface;
use Psr\Log\AbstractLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * Logging of callbacks that match none of our responses: other environments' are expected (info), but one for this
 * environment's own sends shouldn't happen (warning). See also NotifyCallbackTest for the HTTP behaviour.
 */
class CallbackProcessorTest extends KernelTestCase
{
    /** @var AbstractLogger&object{levels: list<string>, contexts: list<array>} */
    private AbstractLogger $logger;

    protected function setUp(): void
    {
        static::getContainer()->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
    }

    private function processor(?string $referencePrefix): CallbackProcessor
    {
        $container = static::getContainer();
        $this->logger = $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $levels = [];
            /** @var list<array> */
            public array $contexts = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->levels[] = (string) $level;
                $this->contexts[] = $context;
            }
        };

        return new CallbackProcessor(
            $container->get(Connection::class),
            $container->get(UuidFactory::class),
            $container->get(ClockInterface::class),
            $logger,
            $referencePrefix,
        );
    }

    public static function unmatchedReferences(): array
    {
        $survey = 'App\\Entity\\Domestic\\Survey::1ef9::9f86d08';

        return [
            'prod: own (unprefixed) reference' => [null, $survey, 'warning'],
            'prod, empty prefix: own reference' => ['', $survey, 'warning'],
            'prod: dev reference' => [null, "dev::{$survey}", 'info'],
            'prod: dev login email (bare prefix)' => [null, 'dev', 'info'],
            'prod: no reference (e.g. a prod login email)' => [null, '', 'info'],
            'dev: own reference' => ['dev', "dev::{$survey}", 'warning'],
            'dev: prod reference' => ['dev', $survey, 'info'],
            'dev: test reference' => ['dev', "test::{$survey}", 'info'],
        ];
    }

    public function testUnmatchedLogDoesNotIncludeTheRecipientHash(): void
    {
        $hash = hash('sha256', '07700900123');
        $this->processor(null)->process(NotifyCallback::TYPE_DELIVERY_RECEIPT, [
            'id' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            'reference' => "App\\Entity\\Domestic\\Survey::1ef9::{$hash}",
            'status' => NotifyCallback::STATUS_DELIVERED,
        ]);

        self::assertStringNotContainsString($hash, json_encode($this->logger->contexts));
        self::assertSame('App\\Entity\\Domestic\\Survey::1ef9', $this->logger->contexts[0]['reference']);
    }

    #[DataProvider('unmatchedReferences')]
    public function testUnmatchedCallbackLogLevel(?string $ownPrefix, string $reference, string $expectedLevel): void
    {
        $outcome = $this->processor($ownPrefix)->process(NotifyCallback::TYPE_DELIVERY_RECEIPT, [
            'id' => 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee',
            'reference' => $reference,
            'status' => NotifyCallback::STATUS_DELIVERED,
        ]);

        self::assertSame(CallbackOutcome::UNMATCHED, $outcome);
        self::assertSame([$expectedLevel], $this->logger->levels);
    }
}
