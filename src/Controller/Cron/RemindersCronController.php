<?php

namespace App\Controller\Cron;

use App\Utility\Reminder\AutomatedRemindersHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RemindersCronController extends AbstractCronController
{
    /**
     * @Route("/reminders/process", name="reminderprocessor")
     */
    public function reminderProcessor(AutomatedRemindersHelper $automatedRemindersHelper): Response
    {
        $automatedRemindersHelper->sendReminders();
        return new Response("Reminders processed.");
    }
}
