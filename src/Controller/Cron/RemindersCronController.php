<?php

namespace App\Controller\Cron;

use App\Utility\RemindersHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class RemindersCronController extends AbstractCronController
{
    /**
     * @Route("/reminders/process", name="reminderprocessor")
     * @param RemindersHelper $remindersHelper
     * @param KernelInterface $kernel
     * @return Response
     */
    public function reminderProcessor(RemindersHelper $remindersHelper, KernelInterface $kernel)
    {
        $remindersHelper->sendReminders();
        return new Response("Reminders processed.");
    }
}
