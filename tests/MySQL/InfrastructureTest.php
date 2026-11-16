<?php

namespace App\Tests\MySQL;

use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Smoke tests for the infrastructure production keeps in MySQL: the Messenger queues (doctrine:// transports) and the
 * lock store (DoctrineDbalStore; maintenance mode and the DVLA import lock). They show the SQL each one generates runs
 * on MySQL and behaves correctly for a single process. They don't test concurrency: nothing here checks that two
 * workers' row locks actually exclude each other.
 */
class InfrastructureTest extends KernelTestCase
{
    use RunsOnMySql;

    public function testDoctrineTransportDeliversAndAcknowledges(): void
    {
        static::bootKernel();
        $container = static::getContainer();
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([]);

        /** @var TransportInterface $transport */
        $transport = $container->get('messenger.transport.async');
        $message = new \ArrayObject(['probe' => 'mysql-transport']);
        $transport->send(new Envelope($message));

        $received = [...$transport->get()];
        $this->assertCount(1, $received);
        $this->assertEquals($message, $received[0]->getMessage());

        // Once delivered (delivered_at set), another get() doesn't hand it out again...
        $this->assertSame([], [...$transport->get()]);

        // ...and once acknowledged, it's gone
        $transport->ack($received[0]);
        $this->assertSame(0, (int) $container->get('doctrine.dbal.default_connection')
            ->fetchOne("SELECT COUNT(*) FROM messenger_messages WHERE queue_name = 'default'"));
    }

    public function testLockIsExclusiveUntilReleased(): void
    {
        static::bootKernel();
        $factory = static::getContainer()->get('lock.default.factory');
        $this->assertInstanceOf(LockFactory::class, $factory);

        $first = $factory->createLock('mysql-suite-probe', 60, false);
        $second = $factory->createLock('mysql-suite-probe', 60, false);

        $this->assertTrue($first->acquire());
        $this->assertFalse($second->acquire(), 'The lock is held');

        $first->release();
        $this->assertTrue($second->acquire(), 'The lock is free again once released');
        $second->release();
    }
}
