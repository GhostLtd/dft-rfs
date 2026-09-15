<?php

namespace App\Controller\Cron;

use App\Utility\Notification\AutomatedNotificationsRunner;
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
}
