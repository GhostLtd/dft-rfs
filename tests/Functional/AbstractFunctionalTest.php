<?php

namespace App\Tests\Functional;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;

abstract class AbstractFunctionalTest extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;
    protected ReferenceRepository $fixtureReferenceRepository;
    protected KernelBrowser $browser;

    #[\Override]
    protected function setUp(): void
    {
        $this->browser = self::createClient([], $this->getServerParameters());
        $this->browser->followRedirects(true);
        $this->browser->setMaxRedirects(10);

        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();

//        $this->browser->disableReboot();
//        $entityManager = $this->getEntityManager();
//        $entityManager->clear();

        parent::setUp();
    }

    protected function getServerParameters(): array
    {
        return [];
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }

    protected function loadFixtures(array $classNames = [], bool $append = false): ?AbstractExecutor
    {
//        $this->setupMessengerTransports();
        $fixtures = $this->databaseTool->loadFixtures($classNames, $append);
        $this->fixtureReferenceRepository = $fixtures->getReferenceRepository();

        return $fixtures;
    }

    protected function setupMessengerTransports(): void
    {
        $app = new Application(self::$kernel);
        $app->setAutoExit(false);
        $app->run(new ArrayInput(['messenger:setup-transports']), new NullOutput());
    }

    // TODO: Abstract out to a separate xpath string helper service
    protected function clickSummaryListActionLink(KernelBrowser $client, string $text): Crawler
    {
        $link = $client
            ->getCrawler()
            ->filterXPath("//main/dl/div/".
                $this->summaryListPart('value', $text).
                "/../dd[@class='govuk-summary-list__actions']/a")
            ->link();

        return $client->click($link);
    }

    protected function summaryListPart(string $part, string $text = null): string
    {
        $textPart = $text ? " and normalize-space()='{$text}'" : '';
        return "*[@class='govuk-summary-list__{$part}'{$textPart}]";
    }

    protected function clickLink(KernelBrowser $client, string $text, string $linkClass=null): Crawler
    {
        return $client->click($this->getLink($client, $text, $linkClass));
    }

    protected function getLink(KernelBrowser $client, string $text, string $linkClass=null): Link
    {
        $parts = [];

        if ($linkClass) {
            $parts[] = $this->classCheck($linkClass);
        }

        $parts[] = "normalize-space()='{$text}'";

        return $client
            ->getCrawler()
            ->filterXPath("//a[".join(' and ', $parts)."]")
            ->link();
    }

    protected function classCheck(string $class): string
    {
        return "contains(concat(' ',normalize-space(@class),' '),' {$class} ')";
    }

    protected function getEntityManager(): EntityManager
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}