<?php

namespace App\Controller\Cron;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class CleanupSurveysController extends AbstractCronController
{
    #[Route(path: '/cleanup/surveys', name: 'cleanup_surveys')]
    public function cleanupSurveysProcessor(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'app:cron:cleanup:surveys',
            []
        );
    }
}
