<?php

namespace App\Controller\Cron;

use App\Utility\Reminder\AutomatedRemindersHelper;
use App\Utility\Reminder\AutomatedRoroRemindersHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RemindersCronController extends AbstractCronController
{
    #[Route(path: '/reminders/process', name: 'reminderprocessor')]
    public function reminderProcessor(
        AutomatedRemindersHelper $automatedRemindersHelper,
        AutomatedRoroRemindersHelper $automatedRoroRemindersHelper
    ): Response
    {
        $automatedRemindersHelper->sendReminders();
        $automatedRoroRemindersHelper->sendReminders();

        return new Response("Reminders processed.");
    }
}
