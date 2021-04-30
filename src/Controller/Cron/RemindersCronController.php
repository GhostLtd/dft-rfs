<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class RemindersCronController extends AbstractCronController
{
    /**
     * @Route("/reminders/process", name="reminderprocessor")
     * @param Request $request
     * @param KernelInterface $kernel
     * @return Response
     * @throws Exception
     */
    public function reminderProcessor(Request $request, KernelInterface $kernel)
    {
        return $this->runCommand(
            $kernel,
            'app:cron:reminders:process',
            [
                '--dry-run' => true,
            ]
        );
    }
}
