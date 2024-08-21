<?php

namespace App\Tests\NewFunctional\Wizard\RoRo;

use App\Messenger\AlphagovNotify\RoRoLoginEmail;
use App\Tests\NewFunctional\AbstractProceduralWizardTest;
use App\Tests\NewFunctional\Wizard\Action\Context;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Panther\DomCrawler\Crawler;

abstract class AbstractRoRoTest extends AbstractProceduralWizardTest
{
    protected EntityManagerInterface $entityManager;
    protected Context $context;


    public function clickSummaryRowLink(string $summaryRowContains, string $linkText): void
    {
        $this->context
            ->outputHeader("Clicking \"{$linkText}\" link for summary row containing \"$summaryRowContains\"")
            ->increaseActionIndex();

        $node = $this->client
            ->getCrawler()
            ->filter('div.govuk-summary-list__row')
            ->reduce(fn(Crawler $node) => str_contains($node->text(), $summaryRowContains))
            ->first();

        $this->client->click($node->selectLink($linkText)->link());
    }

    #[\Override]
    protected function setUp(): void
    {
        $mt = microtime(true);

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->emptyMessageQueue(); // Message table is outside the purview of fixtures

        $this->context = $this->createContext('');
        $this->client->request('GET', '/roro/logout'); // Make sure we're logged out

        // Sleep until the next second to avoid "Login link may only be used once" on fast machines
        // (Symfony's login link generates links with 1-second resolution, and so ends up generating the exact same link)
        $nextSecond = ceil($mt);
        if (microtime(true) <= $nextSecond) {
            time_sleep_until($nextSecond);
        }
    }

    protected function login(string $email): void
    {
        $this->context
            ->outputHeader('Logging in')
            ->increaseActionIndex();

        $this->client->request('GET', '/roro');
        $this->client->submitForm('ro_ro_login_sign_in', [
            'ro_ro_login[username]' => $email,
        ]);

        $personalisation = $this->fetchMessage()->getPersonalisation();
        $this->client->request('GET', $personalisation['login_link']);
        $this->client->submitForm('login');
    }

    protected function fetchMessage(): ?RoRoLoginEmail
    {
        try {
            $messages = $this->entityManager
                ->getConnection()
                ->executeQuery('SELECT * FROM messenger_messages')
                ->fetchAllAssociative();
        } catch (Exception) {
            return null;
        }

        $count = count($messages);

        if ($count === 0) {
            return null;
        } else if ($count > 1) {
            $this->fail('Multiple messages found in message queue');
        } else {
            $serializer = new PhpSerializer();
            $envelope = $serializer->decode($messages[0]);
            $message = $envelope->getMessage();

            $this->assertInstanceOf(RoRoLoginEmail::class, $message);
            return $message;
        }
    }

    protected function emptyMessageQueue(): void
    {
        $this->entityManager
            ->getConnection()
            ->executeQuery('DELETE FROM messenger_messages')
            ->fetchAllAssociative();
    }
}