<?php

namespace App\Tests\Functional;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\TestFixturesBundle\Test\FixturesTrait;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractFunctionalTest extends WebTestCase
{
    use FixturesTrait {
        loadFixtures as _loadFixtures;
    }

    protected ReferenceRepository $fixtureReferenceRepository;

    protected function getFixtureByReference($reference)
    {
        return $this->fixtureReferenceRepository->getReference($reference);
    }

    protected function getBrowserAndLoadFixtures(array $fixtures): KernelBrowser
    {
        $client = self::createClient();
        $client->followRedirects(true);

        $this->loadFixtures($fixtures);

        return $client;
    }

    protected function loadFixtures(array $classNames = [], bool $append = false, ?string $omName = null, string $registryName = 'doctrine', ?int $purgeMode = null): ?AbstractExecutor
    {
        $this->setupMessengerTransports();
        $fixtures = $this->_loadFixtures($classNames, $append, $omName, $registryName, $purgeMode);
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
        $parts = [];

        if ($linkClass) {
            $parts[] = $this->classCheck($linkClass);
        }

        $parts[] = "normalize-space()='{$text}'";

        $link = $client
            ->getCrawler()
            ->filterXPath("//a[".join(' and ', $parts)."]")
            ->link();

        return $client->click($link);
    }

    protected function classCheck(string $class): string
    {
        return "contains(concat(' ',normalize-space(@class),' '),' {$class} ')";
    }
}