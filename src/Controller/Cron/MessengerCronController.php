<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class MessengerCronController extends AbstractCronController
{
    /**
     * @throws Exception
     */
    #[Route(path: '/messenger/consume', name: 'messengerconsumer')]
    public function messengerConsumer(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'messenger:consume',
            [
//                '--limit' => 10,
                '--memory-limit' => '128M',
                '--time-limit' => 290, // die before the next scheduled run time (5 minutes less 10 seconds)
                'receivers' => ['async_notify_high_prio', 'async_notify'],
            ]
        );
    }
}
