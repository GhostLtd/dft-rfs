<?php

namespace App\Controller\Cron;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class CleanupPasscodeUsersCronController extends AbstractCronController
{
    /**
     * @Route("/cleanup/passcode-users", name="cleanup_passcode_users")
     */
    public function cleanupPasscodeUsersProcessor(KernelInterface $kernel): Response
    {
        return $this->runCommand(
            $kernel,
            'app:cron:cleanup:passcode-users',
            []
        );
    }
}
