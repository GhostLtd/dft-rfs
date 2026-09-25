<?php

namespace App\Tests\Command\Dev;

use App\Command\Dev\SendTestSmsCommand;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\MessageHandler;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendTestSmsCommandTest extends TestCase
{
    private function tester(MessageHandler $handler, string $key = 'rfs_team-11111111-1111-1111-1111-111111111111-22222222-2222-2222-2222-222222222222'): CommandTester
    {
        $personalisationHelper = $this->createStub(PersonalisationHelper::class);
        $personalisationHelper->method('getLoginLink')->willReturnMap([['12345678', 'https://frontend.example/c/sample']]);
        return new CommandTester(new SendTestSmsCommand($handler, $personalisationHelper, $this->createStub(EntityManagerInterface::class), $key));
    }

    public function testSendsOneNormalisedSmsAfterConfirmation(): void
    {
        $handler = $this->createMock(MessageHandler::class);
        $handler->expects(self::once())->method('__invoke')
            ->with(self::callback(fn($m) => $m instanceof Sms && $m->getPhoneNumber() === '07700900123'
                && $m->getTemplateId() === Reference::SMS_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_1
                && array_key_exists('registrationMark', $m->getPersonalisation())
                && $m->getPersonalisation()['passcode1'] === '1234-5678'
                && $m->getPersonalisation()['passcode2'] === '8765-4321'
                && $m->getPersonalisation()['loginLink'] === 'https://frontend.example/c/sample'))
            ->willReturn(['id' => 'n-1', 'reference' => 'test-sms', 'content' => ['body' => 'Hello']]);

        $tester = $this->tester($handler);
        $tester->setInputs(['yes']);
        self::assertSame(0, $tester->execute(['mobile' => '+44 7700 900123']));
        self::assertStringContainsString('rfs_team', $tester->getDisplay(), 'shows the key name');
        self::assertStringContainsString('n-1', $tester->getDisplay());
    }

    public function testRefusesALandline(): void
    {
        $handler = $this->createMock(MessageHandler::class);
        $handler->expects(self::never())->method('__invoke');
        self::assertNotSame(0, $this->tester($handler)->execute(['mobile' => '01225 123456']));
    }

    public function testNothingIsSentWithoutConfirmation(): void
    {
        $handler = $this->createMock(MessageHandler::class);
        $handler->expects(self::never())->method('__invoke');
        $tester = $this->tester($handler);
        $tester->setInputs(['no']);
        self::assertNotSame(0, $tester->execute(['mobile' => '07700 900123']));
    }

    public function testWarnsWhenTheKeyLooksLikeATestKey(): void
    {
        $handler = $this->createMock(MessageHandler::class);
        $tester = $this->tester($handler, 'rfs_test-11111111-1111-1111-1111-111111111111-22222222-2222-2222-2222-222222222222');
        $tester->setInputs(['no']);
        $tester->execute(['mobile' => '07700 900123']);
        self::assertStringContainsString('test key', $tester->getDisplay());
    }
}
