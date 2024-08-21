<?php

namespace App\Tests\Utility\Reminder;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\Reminder\AutomatedRemindersHelper;
use App\Utility\Reminder\DisabledRemindersHelper;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBus;

class AutomatedRemindersTest extends KernelTestCase
{
    public function dataDisabledReminders(): array
    {
        return [
            [null, true, true, true],
            ['', true, true, true],
            ['banana', true, true, true],
            ['csrgt', false, true, true],
            ['irhs', true, false, true],
            ['pre-enquiry', true, true, false],
            ['irhs,csrgt', false, false, true],
            ['irhs,pre-enquiry', true, false, false],
            ['csrgt,pre-enquiry', false, true, false],
            ['csrgt,pre-enquiry,irhs', false, false, false],

            ['csrgt,banana,pre-enquiry', false, true, false],
            ['irhs , pre-enquiry', true, false, false],
            ['orange,irhs,banana,pre-enquiry', true, false, false],
        ];
    }

    /**
     * @dataProvider dataDisabledReminders
     */
    public function testDisabledReminders(?string $disabledRemindersEnv, bool $expectedCsrgt, bool $expectedIrhs, bool $expectedPreEnquiry): void
    {
        $automatedRemindersHelperMock = $this->getAutomatedRemindersMock($disabledRemindersEnv);

        $reflClass = new \ReflectionClass($automatedRemindersHelperMock);
        $reflMethod = $reflClass->getMethod('getSurveysForReminding');
        $reflMethod->setAccessible(true);

        $reminderGroups = $reflMethod->invoke($automatedRemindersHelperMock);

        $expected = [
            DomesticSurvey::class => $expectedCsrgt,
            InternationalSurvey::class => $expectedIrhs,
            PreEnquiry::class => $expectedPreEnquiry,
        ];

        foreach($reminderGroups as $reminderNumber => $reminders) {
            foreach($expected as $class => $willBePresent) {
                if ($willBePresent) {
                    $this->assertArrayHasKey($class, $reminders);
                } else {
                    $this->assertArrayNotHasKey($class, $reminders);
                }
            }
        }
    }

    protected function getAutomatedRemindersMock(?string $disabledReminderEnv): AutomatedRemindersHelper&MockObject
    {
        $disabledRemindersHelper = new DisabledRemindersHelper($disabledReminderEnv);

        // These methods will be overridden to return []
        $methods = [
            'getSurveysForReminder1',
            'getSurveysForReminder2',
            'getPreEnquiriesForReminder1',
        ];

        $automatedRemindersHelperMock = $this->getMockBuilder(AutomatedRemindersHelper::class)
            ->setConstructorArgs([
                $disabledRemindersHelper,
                $this->createStub(EntityManager::class),
                $this->createStub(Logger::class),
                $this->createStub(MessageBus::class),
                $this->createStub(PersonalisationHelper::class),
            ])
            ->onlyMethods($methods)
            ->getMock();

        foreach($methods as $method) {
            $automatedRemindersHelperMock
                ->method($method)
                ->willReturn([]);
        }

        return $automatedRemindersHelperMock;
    }
}