<?php

namespace App\Controller\Cron;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class MessengerCronController extends AbstractCronController
{
    /**
     * @Route("/messenger/consume", name="messengerconsumer")
     * @param Request $request
     * @param KernelInterface $kernel
     * @return Response
     * @throws Exception
     */
    public function messengerConsumer(Request $request, KernelInterface $kernel)
    {
        return $this->runCommand(
            $kernel,
            'messenger:consume',
            [
//                '--limit' => 10,
                '--memory-limit' => '128M',
                '--time-limit' => 290, // die before the next scheduled run time (5 minutes less 10 seconds)
                'receivers' => ['async_notify'],
            ]
        );
    }
}
