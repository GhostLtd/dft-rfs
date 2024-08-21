<?php

namespace App\Controller\Cron;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class CreateRoroSurveysCronController extends AbstractCronController
{
    #[Route(path: '/roro/create-surveys', name: 'roro_create_surveys')]
    public function createRoroSurveys(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'app:cron:roro:create-surveys',
            []
        );
    }
}
