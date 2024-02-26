<?php

namespace App\Tests\Utility;

use App\Utility\NotificationInterceptionService;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class AbstractNotificationInterceptionServiceTest extends WebTestCase
{
    protected EntityManagerInterface $entityManager;
    protected NotificationInterceptionService $interceptService;

    #[\Override]
    public function setUp(): void
    {
        $container = static::getContainer();

        $databaseTools = $container->get(DatabaseToolCollection::class)->get();
        $databaseTools->loadFixtures(); // Purge database

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->interceptService = $container->get(NotificationInterceptionService::class);
    }
}
