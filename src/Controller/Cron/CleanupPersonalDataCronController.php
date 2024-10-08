<?php

namespace App\Controller\Cron;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

class CleanupPersonalDataCronController extends AbstractCronController
{
    #[Route(path: '/cleanup/personal-data', name: 'cleanup_personal_data')]
    public function cleanupPersonalDataProcessor(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'app:cron:cleanup:personal-data',
            []
        );
    }
}
