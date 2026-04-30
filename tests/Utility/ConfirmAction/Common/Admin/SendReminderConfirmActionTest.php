<?php

namespace App\Tests\Utility\ConfirmAction\Common\Admin;

use App\Entity\Domestic\Survey as DomesticSurvey;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\ActionFailedException;
use App\Utility\ConfirmAction\Common\Admin\SendReminderConfirmAction;
use App\Utility\Reminder\ManualReminderHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SendReminderConfirmActionTest extends TestCase
{
    public function testDoConfirmedActionThrowsActionFailedExceptionWhenReminderNotSent(): void
    {
        $reminderHelperMock = $this->createMock(ManualReminderHelper::class);
        $reminderHelperMock
            ->method('sendManualReminder')
            ->willReturn(false);

        $confirmAction = new SendReminderConfirmAction(
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(RequestStack::class),
            $this->createMock(EntityManagerInterface::class),
            $reminderHelperMock,
        );

        $confirmAction->setSubject(new DomesticSurvey());

        $this->expectException(ActionFailedException::class);
        $confirmAction->doConfirmedAction(null);
    }

    public function testDoConfirmedActionDoesNotThrowWhenReminderSent(): void
    {
        $reminderHelperMock = $this->createMock(ManualReminderHelper::class);
        $reminderHelperMock
            ->method('sendManualReminder')
            ->willReturn(true);

        $confirmAction = new SendReminderConfirmAction(
            $this->createMock(FormFactoryInterface::class),
            $this->createMock(RequestStack::class),
            $this->createMock(EntityManagerInterface::class),
            $reminderHelperMock,
        );

        $confirmAction->setSubject(new DomesticSurvey());

        $confirmAction->doConfirmedAction(null);
        $this->addToAssertionCount(1);
    }
}
