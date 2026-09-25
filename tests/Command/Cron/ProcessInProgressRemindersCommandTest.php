<?php

namespace App\Tests\Command\Cron;

use App\Command\Cron\ProcessInProgressRemindersCommand;
use App\Utility\Notification\AutomatedNotificationsRunner;
use App\Utility\Reminder\DomesticInProgressReminders;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ProcessInProgressRemindersCommandTest extends TestCase
{
    public function testRunsOnlyTheInProgressFamilyThroughTheRunner(): void
    {
        $reminders = $this->createMock(DomesticInProgressReminders::class);
        $runner = $this->createMock(AutomatedNotificationsRunner::class);
        $runner->expects(self::once())->method('runOne')->with(self::identicalTo($reminders));
        $runner->expects(self::never())->method('run');

        self::assertSame(Command::SUCCESS, (new CommandTester(new ProcessInProgressRemindersCommand($runner, $reminders)))->execute([]));
    }

    public function testRejectsANonNumericSmsCap(): void
    {
        $reminders = $this->createMock(DomesticInProgressReminders::class);
        $runner = $this->createMock(AutomatedNotificationsRunner::class);
        $runner->expects(self::never())->method('runOne');

        $tester = new CommandTester(new ProcessInProgressRemindersCommand($runner, $reminders));
        self::assertSame(Command::INVALID, $tester->execute(['--dry-run' => true, '--sms-cap' => 'not-a-number']));
        self::assertStringContainsString('--sms-cap', $tester->getDisplay());
    }

    public function testRejectsAnUnparseableAt(): void
    {
        $reminders = $this->createMock(DomesticInProgressReminders::class);
        $runner = $this->createMock(AutomatedNotificationsRunner::class);
        $runner->expects(self::never())->method('runOne');

        $tester = new CommandTester(new ProcessInProgressRemindersCommand($runner, $reminders));
        self::assertSame(Command::INVALID, $tester->execute(['--dry-run' => true, '--at' => 'not-a-date']));
        self::assertStringContainsString('--at', $tester->getDisplay());
    }
}
