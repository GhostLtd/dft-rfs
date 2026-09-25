<?php

namespace App\Tests\Command\Dev;

use Alphagov\Notifications\Client;
use Alphagov\Notifications\Exception\ApiException;
use App\Tests\DataFixtures\Rehearsal\BaselineReferenceFixtures;
use App\Utility\Notification\NotificationTemplates;
use App\Utility\Rehearsal\ScenarioSeeder;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Tester\CommandTester;

class CheckNotifyTemplatesCommandTest extends KernelTestCase
{
    /** @var list<array{0: string, 1: array}> */
    private array $previews = [];

    /** @param \Closure(\Psr\Container\ContainerInterface): void|null $beforeRun runs after seeding (and the manifest-clear), before the command executes */
    private function check(?string $failTemplateId = null, bool $seed = true, ?\Closure $beforeRun = null): CommandTester
    {
        $c = static::getContainer();
        $c->get(DatabaseToolCollection::class)->get()->loadFixtures([BaselineReferenceFixtures::class]);
        $c->set('clock', new MockClock('2026-09-10 09:00:00', 'Europe/London'));

        $client = $this->createMock(Client::class);
        $client->method('previewTemplate')->willReturnCallback(function (string $id, array $personalisation) use ($failTemplateId) {
            $this->previews[] = [$id, $personalisation];
            if ($id === $failTemplateId) {
                throw new ApiException('Bad', 400, ['errors' => [['error' => 'BadRequestError', 'message' => 'Missing personalisation: something']]], $this->createMock(ResponseInterface::class));
            }
            return ['id' => $id, 'body' => 'rendered'];
        });
        $c->set(Client::class, $client);

        if ($seed) {
            $c->get(ScenarioSeeder::class)->seed(new \DateTimeImmutable('2026-09-10 09:00:00', new \DateTimeZone('Europe/London')), ['from' => '2026-08-15', 'disabled' => [], 'smsCap' => 20000]);
            $c->get(\Doctrine\ORM\EntityManagerInterface::class)->clear(); // the command runs as its own "process"
        }
        $beforeRun?->__invoke($c);

        $tester = new CommandTester((new Application(static::$kernel))->find('app:dev:check-notify-templates'));
        $tester->execute([]);
        return $tester;
    }

    public function testPreviewsEveryTemplateWithTheRealPayload(): void
    {
        $tester = $this->check();
        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());

        $expected = 0;
        foreach ([\App\Entity\Domestic\Survey::class, \App\Entity\International\Survey::class, \App\Entity\PreEnquiry\PreEnquiry::class, \App\Entity\RoRo\Survey::class] as $class) {
            foreach (NotificationTemplates::typesFor($class) as $type) {
                $expected += count(NotificationTemplates::for($class, $type));
            }
        }
        self::assertCount($expected, $this->previews);

        $letters = array_filter($this->previews, fn($p) => $p[0] === \App\Utility\AlphagovNotify\Reference::LETTER_DOMESTIC_SURVEY_REMINDER_1);
        self::assertArrayHasKey('address_line_1', array_values($letters)[0][1]);
        $roro = array_filter($this->previews, fn($p) => $p[0] === \App\Utility\AlphagovNotify\Reference::EMAIL_RORO_REMINDER_1);
        self::assertArrayHasKey('route_names', array_values($roro)[0][1]);

        // Domestic (CSRGT) and International (IRHS) surveys are both mapped by the entity class App\Entity\*\Survey;
        // the row label must disambiguate them rather than collapsing both to "Survey".
        self::assertStringContainsString('CSRGT reminder-1 letter', $tester->getDisplay());
        self::assertStringContainsString('IRHS reminder-1 letter', $tester->getDisplay());
    }

    public function testSmsIsPreviewedWithoutChargingEitherLimitOrRecordingAnything(): void
    {
        $tables = ['sms_usage', 'sms_recipient_day', 'sms_attempt', 'domestic_survey_notification', 'messenger_messages'];
        $snapshot = fn($c) => array_map(
            fn(string $table) => $c->get(\Doctrine\ORM\EntityManagerInterface::class)->getConnection()->fetchAllAssociative("SELECT * FROM {$table}"),
            array_combine($tables, $tables),
        );
        $before = null;
        $tester = $this->check(beforeRun: function ($c) use (&$before, $snapshot): void {
            $before = $snapshot($c);
        });
        self::assertSame(0, $tester->getStatusCode(), $tester->getDisplay());

        $sms = array_filter($this->previews, fn($p) => $p[0] === \App\Utility\AlphagovNotify\Reference::SMS_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_1);
        self::assertNotEmpty($sms, 'the SMS template was previewed');
        self::assertSame($before, $snapshot(static::getContainer()));
    }

    public function testNotifyErrorIsReportedAndCheckContinues(): void
    {
        $tester = $this->check(\App\Utility\AlphagovNotify\Reference::EMAIL_DOMESTIC_SURVEY_REMINDER_1);
        self::assertNotSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Missing personalisation', $tester->getDisplay());
        self::assertGreaterThan(5, count($this->previews), 'later templates were still previewed');
    }

    public function testRefusesWithoutASeed(): void
    {
        $tester = $this->check(seed: false);
        self::assertNotSame(0, $tester->getStatusCode());
        self::assertStringContainsString('Seed first', $tester->getDisplay());
    }

    public function testMissingSampleForAChannelIsReportedAndOtherChannelsStillRun(): void
    {
        $tester = $this->check(beforeRun: function ($c): void {
            // Leaves every seeded CSRGT survey without a mobile number, so no sample exists for the in-progress
            // reminders' sms channel; the invite/reminder letter and email channels are unaffected.
            $em = $c->get(\Doctrine\ORM\EntityManagerInterface::class);
            $em->getConnection()->executeStatement('UPDATE domestic_survey_response SET contact_telephone = NULL');
            $em->clear();
        });

        self::assertNotSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        self::assertStringContainsString('no seeded survey can receive this channel', $display);
        self::assertStringContainsString('CSRGT in-progress-reminder-1 sms', $display);
        self::assertStringContainsString('CSRGT in-progress-reminder-2 sms', $display);
        self::assertStringContainsString('CSRGT in-progress-reminder-3 sms', $display);

        // The email and letter channels don't depend on the telephone number, so they were still previewed.
        $emails = array_filter($this->previews, fn($p) => $p[0] === \App\Utility\AlphagovNotify\Reference::EMAIL_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_1);
        self::assertNotEmpty($emails, 'the in-progress reminder email template was still previewed');
        $letters = array_filter($this->previews, fn($p) => $p[0] === \App\Utility\AlphagovNotify\Reference::LETTER_DOMESTIC_SURVEY_REMINDER_1);
        self::assertNotEmpty($letters, 'the reminder letter template was still previewed');
    }
}
