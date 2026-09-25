<?php

namespace App\Controller\Cron;

use App\Utility\Notification\AutomatedNotificationsRunner;
use App\Utility\Reminder\DomesticInProgressReminders;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RemindersCronController extends AbstractCronController
{
    #[Route(path: '/reminders/process', name: 'reminderprocessor')]
    public function reminderProcessor(AutomatedNotificationsRunner $runner): Response
    {
        $runner->run();
        return new Response("Reminders processed.");
    }

    #[Route(path: '/reminders/process-in-progress', name: 'reminderprocessor_in_progress')]
    public function inProgressReminderProcessor(AutomatedNotificationsRunner $runner, DomesticInProgressReminders $reminders): Response
    {
        $runner->runOne($reminders);
        return new Response("In-progress reminders processed.");
    }
}
